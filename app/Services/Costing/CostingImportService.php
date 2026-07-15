<?php

namespace App\Services\Costing;

use App\Models\DocumentRevision;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CostingImportService
{
    public function preparePartlistImport(array $validated, $request): array
    {
        $trackingRevisionId = isset($validated['tracking_revision_id']) ? (int) $validated['tracking_revision_id'] : null;
        $uploadedPartlistFile = $request->file('import_partlist_file');
        $uploadErrorCode = (int) ($_FILES['import_partlist_file']['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadedPartlistFile) {
            if (!$uploadedPartlistFile->isValid()) {
                $errorCode = (int) $uploadedPartlistFile->getError();
                return ['error' => 'Upload file partlist gagal: ' . $this->uploadErrorCodeToMessage($errorCode)];
            }

            $ext = strtolower((string) $uploadedPartlistFile->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                return ['error' => 'Format file harus .xlsx atau .xls sesuai template partlist.'];
            }

            if ($uploadedPartlistFile->getSize() > (20 * 1024 * 1024)) {
                return ['error' => 'Ukuran file partlist terlalu besar (maks 20MB).'];
            }
        } else {
            if ($uploadErrorCode !== UPLOAD_ERR_NO_FILE) {
                return ['error' => 'Upload file partlist gagal: ' . $this->uploadErrorCodeToMessage($uploadErrorCode)];
            }

            if ($uploadErrorCode === UPLOAD_ERR_NO_FILE && !$trackingRevisionId) {
                return ['warning' => 'Silakan pilih file partlist terlebih dahulu sebelum import.'];
            }
        }

        $importResult = $this->loadPartlistMaterialRows($trackingRevisionId, $uploadedPartlistFile);
        if (!empty($importResult['error'])) {
            return ['error' => $importResult['error']];
        }

        $rows = array_values($importResult['rows']);
        if (count($rows) === 0) {
            return ['warning' => 'Data partlist tidak ditemukan. Pastikan data diisi mulai kolom D-J dari baris 12 ke bawah (sesuai template).'];
        }

        return ['rows' => $rows];
    }

    public function prepareCycleTimeImport($request): array
    {
        $uploadedCycleTimeFile = $request->file('import_cycle_time_file');
        $uploadErrorCode = (int) ($_FILES['import_cycle_time_file']['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadedCycleTimeFile) {
            if (!$uploadedCycleTimeFile->isValid()) {
                $errorCode = (int) $uploadedCycleTimeFile->getError();
                return ['error' => 'Upload file Cycle Time gagal: ' . $this->uploadErrorCodeToMessage($errorCode)];
            }

            $ext = strtolower((string) $uploadedCycleTimeFile->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                return ['error' => 'Format file Cycle Time harus .xlsx atau .xls.'];
            }

            if ($uploadedCycleTimeFile->getSize() > (20 * 1024 * 1024)) {
                return ['error' => 'Ukuran file Cycle Time terlalu besar (maks 20MB).'];
            }
        } elseif ($uploadErrorCode !== UPLOAD_ERR_NO_FILE) {
            return ['error' => 'Upload file Cycle Time gagal: ' . $this->uploadErrorCodeToMessage($uploadErrorCode)];
        } else {
            return ['warning' => 'Silakan pilih file Cycle Time terlebih dahulu sebelum import.'];
        }

        $importResult = $this->loadCycleTimeRows($uploadedCycleTimeFile);
        if (!empty($importResult['error'])) {
            return ['error' => $importResult['error']];
        }

        $rows = array_values($importResult['rows']);
        if (count($rows) === 0) {
            return ['warning' => 'Data Cycle Time tidak ditemukan. Pastikan data No/Process/Qty/Time/Area diisi sesuai template (B18, C18, D17, G18, I18 ke bawah).'];
        }

        return ['rows' => $rows];
    }

    private function loadPartlistMaterialRows(?int $trackingRevisionId, $uploadedPartlistFile = null): array
    {
        $localTempPath = null;

        try {
            if ($uploadedPartlistFile instanceof UploadedFile) {
                if (!$uploadedPartlistFile->isValid()) {
                    return ['rows' => [], 'error' => 'File yang diupload tidak valid.'];
                }

                $spreadsheet = IOFactory::load($uploadedPartlistFile->getRealPath());
            } elseif ($trackingRevisionId) {
                $trackingRevision = DocumentRevision::find($trackingRevisionId);
                if (!$trackingRevision || empty($trackingRevision->partlist_file_path)) {
                    return ['rows' => [], 'error' => 'File partlist belum tersedia pada revisi tracking yang dipilih.'];
                }

                $storagePath = storage_path('app/private/' . ltrim((string) $trackingRevision->partlist_file_path, '/'));
                if (!file_exists($storagePath)) {
                    return ['rows' => [], 'error' => 'File partlist tidak ditemukan di server.'];
                }

                $sourceExt = strtolower((string) pathinfo($storagePath, PATHINFO_EXTENSION));
                $tempExt = in_array($sourceExt, ['xlsx', 'xls'], true) ? $sourceExt : 'xlsx';
                $localTempPath = tempnam(sys_get_temp_dir(), 'partlist_');
                if ($localTempPath === false) {
                    return ['rows' => [], 'error' => 'Gagal menyiapkan file partlist sementara.'];
                }
                $renamedTempPath = $localTempPath . '.' . $tempExt;
                if (!@rename($localTempPath, $renamedTempPath)) {
                    @unlink($localTempPath);
                    return ['rows' => [], 'error' => 'Gagal menyiapkan file partlist sementara.'];
                }
                $localTempPath = $renamedTempPath;

                if (!@copy($storagePath, $localTempPath)) {
                    @unlink($localTempPath);
                    return ['rows' => [], 'error' => 'Gagal menyalin file partlist ke penyimpanan sementara.'];
                }

                $spreadsheet = IOFactory::load($localTempPath);
            } else {
                return ['rows' => [], 'error' => 'Silakan pilih file partlist terlebih dahulu sebelum import.'];
            }

            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $rows = [];

            for ($row = 12; $row <= $highestRow; $row++) {
                $partNo = trim((string) $sheet->getCell('D' . $row)->getCalculatedValue());
                $partName = trim((string) $sheet->getCell('E' . $row)->getCalculatedValue());
                $qtyReq = trim((string) $sheet->getCell('F' . $row)->getCalculatedValue());
                $unit = trim((string) $sheet->getCell('G' . $row)->getCalculatedValue());
                $idCode = trim((string) $sheet->getCell('H' . $row)->getCalculatedValue());
                $proCode = trim((string) $sheet->getCell('I' . $row)->getCalculatedValue());
                $supplier = trim((string) $sheet->getCell('J' . $row)->getCalculatedValue());

                if ($partNo === '' && $partName === '' && $qtyReq === '' && $unit === '' && $idCode === '' && $proCode === '' && $supplier === '') {
                    continue;
                }

                $rows[] = [
                    'row_no' => (string) count($rows) + 1,
                    'part_no' => $partNo,
                    'part_name' => $partName,
                    'qty_req' => $qtyReq,
                    'unit' => $unit,
                    'id_code' => $idCode,
                    'pro_code' => $proCode,
                    'supplier' => $supplier,
                    'amount1' => '',
                    'unit_price_basis' => '',
                    'qty_moq' => '',
                    'cn_type' => '',
                    'import_tax' => '',
                    'currency' => 'IDR',
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return ['rows' => $rows];
        } catch (\Throwable $e) {
            return ['rows' => [], 'error' => 'Gagal membaca file partlist: ' . $e->getMessage()];
        } finally {
            if ($localTempPath && file_exists($localTempPath)) {
                @unlink($localTempPath);
            }
        }
    }

    private function loadCycleTimeRows($uploadedCycleTimeFile): array
    {
        try {
            if (!$uploadedCycleTimeFile instanceof UploadedFile || !$uploadedCycleTimeFile->isValid()) {
                return ['rows' => [], 'error' => 'File Cycle Time yang diupload tidak valid.'];
            }

            $spreadsheet = IOFactory::load($uploadedCycleTimeFile->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $rows = [];

            for ($row = 18; $row <= $highestRow; $row++) {
                $no = trim((string) $sheet->getCell('B' . $row)->getCalculatedValue());
                $process = trim((string) $sheet->getCell('C' . $row)->getCalculatedValue());
                $qty = trim((string) $sheet->getCell('D' . $row)->getCalculatedValue());
                $timeSec = trim((string) $sheet->getCell('G' . $row)->getCalculatedValue());
                $area = trim((string) $sheet->getCell('I' . $row)->getCalculatedValue());

                if ($no === '' && $process === '' && $qty === '' && $timeSec === '' && $area === '') {
                    continue;
                }

                $qtyValue = is_numeric(str_replace(',', '.', $qty)) ? (float) str_replace(',', '.', $qty) : 0;
                $timeSecValue = is_numeric(str_replace(',', '.', $timeSec)) ? (float) str_replace(',', '.', $timeSec) : 0;
                $timePerQty = $qtyValue > 0 ? ($timeSecValue / $qtyValue) : 0;

                $rows[] = [
                    'no' => $no !== '' ? $no : (string) (count($rows) + 1),
                    'process' => $process,
                    'qty' => $qtyValue,
                    'time_hour' => round($timeSecValue / 3600, 6),
                    'time_sec' => $timeSecValue,
                    'time_sec_per_qty' => round($timePerQty, 6),
                    'cost_per_sec' => 0,
                    'cost_per_unit' => 0,
                    'area_of_process' => $area,
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return ['rows' => $rows];
        } catch (\Throwable $e) {
            return ['rows' => [], 'error' => 'Gagal membaca file Cycle Time: ' . $e->getMessage()];
        }
    }

    private function uploadErrorCodeToMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas maksimum upload di server.',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian. Silakan coba lagi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload tidak tersedia di server.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menyimpan file upload.',
            UPLOAD_ERR_EXTENSION => 'Upload dibatalkan oleh ekstensi PHP di server.',
            default => 'Terjadi kesalahan saat upload file.',
        };
    }
}
