<?php

namespace App\Services\Assistant;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Services\TrackingDocument\TrackingDocumentFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PartlistProjectImportService
{
    public function __construct(private readonly TrackingDocumentFileService $fileService)
    {
    }

    public function preview(UploadedFile $file, ?array $validationRules = null): array
    {
        $parsed = $this->parse($file, $validationRules ?? []);
        $project = $parsed['project'];
        $issues = $parsed['issues'];

        foreach (['customer', 'model', 'part_no', 'part_name'] as $field) {
            if (trim((string) ($project[$field] ?? '')) === '') {
                $issues[] = 'Field project wajib belum terbaca: ' . $field;
            }
        }

        return [
            'status' => $issues === [] ? 'success' : 'warning',
            'message' => $issues === []
                ? 'Partlist siap dibuat menjadi New Project.'
                : 'Partlist terbaca, tetapi masih ada data yang perlu dicek sebelum dibuat project.',
            'workflow' => 'create_new_project',
            'project' => $project,
            'summary' => $parsed['summary'],
            'sample_rows' => array_slice($parsed['rows'], 0, 5),
            'issues' => $issues,
        ];
    }

    public function createProject(UploadedFile $file, ?array $validationRules = null): array
    {
        $preview = $this->preview($file, $validationRules ?? []);
        if (!in_array($preview['status'], ['success'], true)) {
            return [
                'status' => 'warning',
                'message' => 'Project belum dibuat karena data wajib partlist belum lengkap.',
                'preview' => $preview,
            ];
        }

        $projectData = $preview['project'];

        return DB::transaction(function () use ($file, $projectData, $preview) {
            $customerName = trim((string) $projectData['customer']);
            $businessCategoryName = trim((string) ($projectData['business_category'] ?: 'WIRING HARNESS'));
            $model = trim((string) $projectData['model']);
            $partNo = trim((string) $projectData['part_no']);
            $partName = trim((string) $projectData['part_name']);
            $picEngineering = trim((string) ($projectData['pic_engineering'] ?: 'Engineering'));
            $picMarketing = trim((string) ($projectData['pic_marketing'] ?: 'Marketing'));
            $receivedDate = $projectData['received_date'] ?: now()->toDateString();

            Customer::firstOrCreate(
                ['name' => $customerName],
                ['code' => Str::upper(Str::slug($customerName, '_'))]
            );

            $categoryCode = Str::upper(Str::slug($businessCategoryName, '_')) ?: 'WIRING_HARNESS';
            BusinessCategory::firstOrCreate(
                ['name' => $businessCategoryName],
                ['code' => $categoryCode]
            );

            $product = Product::firstOrCreate(
                ['code' => $categoryCode],
                ['name' => $businessCategoryName]
            );
            if ((string) $product->name !== $businessCategoryName) {
                $product->update(['name' => $businessCategoryName]);
            }

            $projectKey = $this->makeProjectKey($customerName, $model, $partNo, $partName);
            $project = DocumentProject::firstOrCreate(
                ['project_key' => $projectKey],
                [
                    'product_id' => $product->id,
                    'customer' => $customerName,
                    'model' => $model,
                    'part_number' => $partNo,
                    'part_name' => $partName,
                ]
            );

            if ((int) ($project->product_id ?? 0) !== (int) $product->id) {
                $project->update(['product_id' => $product->id]);
            }

            $nextVersion = (int) $project->revisions()->max('version_number') + 1;
            $storedPartlist = $this->fileService->storeUploadedFile($file, 'partlist');

            $revision = DocumentRevision::create([
                'document_project_id' => $project->id,
                'version_number' => $nextVersion,
                'received_date' => $receivedDate,
                'pic_engineering' => $picEngineering,
                'pic_marketing' => $picMarketing,
                'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
                'a00' => 'belum_ada',
                'a00_received_date' => null,
                'a00_document_original_name' => null,
                'a00_document_file_path' => null,
                'a04' => 'belum_ada',
                'a04_received_date' => null,
                'a04_document_original_name' => null,
                'a04_document_file_path' => null,
                'a05' => 'belum_ada',
                'a05_received_date' => null,
                'a05_document_original_name' => null,
                'a05_document_file_path' => null,
                'partlist_original_name' => $storedPartlist['name'],
                'partlist_file_path' => $storedPartlist['path'],
                'partlist_update_count' => 0,
                'partlist_updated_at' => null,
                'umh_original_name' => '',
                'umh_file_path' => '',
                'umh_update_count' => 0,
                'umh_updated_at' => null,
                'notes' => 'Dibuat otomatis dari Costing Assistant Partlist workflow. Rows: ' . $preview['summary']['total_rows'],
                'change_remark' => $nextVersion === 1
                    ? 'Dokumen awal dibuat dari upload partlist via Costing Assistant.'
                    : 'Revisi baru dibuat dari upload partlist via Costing Assistant.',
            ]);

            return [
                'status' => 'success',
                'message' => 'New Project berhasil dibuat dari partlist.',
                'project' => [
                    'id' => $project->id,
                    'customer' => $project->customer,
                    'model' => $project->model,
                    'part_number' => $project->part_number,
                    'part_name' => $project->part_name,
                ],
                'revision' => [
                    'id' => $revision->id,
                    'version_label' => $revision->version_label,
                    'status' => $revision->status_label,
                ],
                'redirect_url' => route('project', [], false),
            ];
        });
    }

    private function parse(UploadedFile $file, array $validationRules): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        // Cari header bukan cuma di baris 1, tapi sampai baris 20 (karena ini template Partlist / excel yang agak turun)
        $headers = [];
        $headerRow = 1;

        for ($row = 1; $row <= min(20, $highestRow); $row++) {
            $candidateHeaders = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $header = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
                $normalized = $this->normalizeHeader($header);
                if ($normalized !== '') {
                    $candidateHeaders[$normalized] = $col;
                }
            }

            // Lebih fleksibel: asalkan ada part_no atau part_name
            $hasPart = isset($candidateHeaders['part_no']) || isset($candidateHeaders['assy_no']) || isset($candidateHeaders['part_name']) || isset($candidateHeaders['part_number']) || isset($candidateHeaders['material_code']) || isset($candidateHeaders['id_code']);
            $hasQty = isset($candidateHeaders['qty']) || isset($candidateHeaders['quantity']) || isset($candidateHeaders['qty_req']) || isset($candidateHeaders['usage_qty']);
            
            if ($hasPart && $hasQty) {
                $headers = $candidateHeaders;
                $headerRow = $row;
                break;
            }
        }

        // Fallback kalau nggak nemu pola di atas, pakai row 1 kayak originalnya
        if (empty($headers)) {
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $header = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
                $normalized = $this->normalizeHeader($header);
                if ($normalized !== '') {
                    $headers[$normalized] = $col;
                }
            }
        }

        $mapping = $this->mapping($validationRules, $headers);
        
        // Cek jika ini format khusus tanpa string header di Partlist Excel (asumsi E=part, G=name, H=qty)
        if (isset($validationRules['mapping']) && empty($mapping['part_no']) && empty($mapping['part_name']) && $headerRow === 1) {
            $mapping['part_no'] = 5; // E
            $mapping['part_name'] = 7; // G
            $mapping['qty'] = 8; // H
            $headerRow = 11; // Data dimulai di baris 12 (karena loopnya $headerRow + 1)
        }

        $rows = [];
        $project = [
            'customer' => null,
            'model' => null,
            'business_category' => null,
            'part_no' => null,
            'part_name' => null,
            'qty' => null,
            'pic_engineering' => null,
            'pic_marketing' => null,
            'received_date' => null,
        ];
        
        // Ekstraksi info file dari template (F4, F5, F6, F7)
        if (isset($validationRules['mapping'])) {
            $assyNo = trim((string) $sheet->getCell('F4')->getFormattedValue());
            $assyName = trim((string) $sheet->getCell('F5')->getFormattedValue());
            $customer = trim((string) $sheet->getCell('F6')->getFormattedValue());
            $model = trim((string) $sheet->getCell('F7')->getFormattedValue());

            if ($assyNo !== '') $project['part_no'] = $assyNo;
            if ($assyName !== '') $project['part_name'] = $assyName;
            if ($customer !== '') $project['customer'] = $customer;
            if ($model !== '') $project['model'] = $model;
        }

        $issues = [];
        $seenPartNo = [];
        $duplicatePartNo = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $item = [];
            foreach ($mapping as $field => $columnIndex) {
                if (!$columnIndex) {
                    continue;
                }
                $item[$field] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $row)->getFormattedValue());
            }

            if (collect($item)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            foreach ($project as $field => $currentValue) {
                if (($currentValue === null || $currentValue === '') && !empty($item[$field])) {
                    $project[$field] = $item[$field];
                }
            }

            $partNo = mb_strtolower(trim((string) ($item['part_no'] ?? '')));
            if ($partNo !== '') {
                if (isset($seenPartNo[$partNo])) {
                    $duplicatePartNo++;
                }
                $seenPartNo[$partNo] = true;
            }

            $rows[] = $item;
        }

        if ($highestRow <= $headerRow || $rows === []) {
            $issues[] = 'Excel tidak memiliki baris data setelah header.';
        }

        foreach (['customer', 'model'] as $field) {
            if (empty($project[$field]) && empty($mapping[$field])) {
                $issues[] = 'Data project wajib belum ditemukan: ' . $field;
            }
        }
        foreach (['part_no', 'part_name'] as $field) {
            if (empty($mapping[$field])) {
                $issues[] = 'Mapping kolom part list belum ditemukan: ' . $field ;
            }
        }

        // Jangan masukkan duplicate part_no ke issues karena akan menghalangi tombol create project.
        // Di dalam partlist, duplicate part no sangat wajar terjadi.
        // if ($duplicatePartNo > 0) {
        //     $issues[] = 'Ditemukan ' . $duplicatePartNo . ' duplikasi part_no pada file.';
        // }

        return [
            'project' => $project,
            'rows' => $rows,
            'issues' => $issues,
            'summary' => [
                'sheet_name' => $sheet->getTitle(),
                'total_rows' => count($rows),
                'total_columns' => count($headers),
                'headers' => array_keys($headers),
                'duplicate_part_no' => $duplicatePartNo,
            ],
        ];
    }

    private function mapping(array $validationRules, array $headers): array
    {
        $configured = $validationRules['mapping'] ?? [];
        $aliases = [
            'customer' => ['customer', 'customer_name', 'cust', 'nama_customer'],
            'model' => ['model', 'model_name', 'vehicle_model'],
            'business_category' => ['business_category', 'category', 'product', 'product_name'],
            'part_no' => ['part_no', 'part_number', 'assy_no', 'assy_number', 'assy'],
            'part_name' => ['part_name', 'part_description', 'assy_name', 'description', 'nama_part'],
            'qty' => ['qty', 'quantity', 'jumlah'],
            'pic_engineering' => ['pic_engineering', 'engineering_pic', 'pic_eng'],
            'pic_marketing' => ['pic_marketing', 'marketing_pic', 'pic_mkt'],
            'received_date' => ['received_date', 'tanggal_terima', 'date'],
        ];

        $mapping = [];
        foreach ($aliases as $field => $candidates) {
            if (!empty($configured[$field])) {
                $candidate = $this->normalizeHeader((string) $configured[$field]);
                $mapping[$field] = $headers[$candidate] ?? null;
                continue;
            }

            $mapping[$field] = null;
            foreach ($candidates as $candidate) {
                $candidate = $this->normalizeHeader($candidate);
                if (isset($headers[$candidate])) {
                    $mapping[$field] = $headers[$candidate];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/i', '_', $header) ?: '';
        return trim($header, '_');
    }

    private function makeProjectKey(string $customer, string $model, string $partNumber, string $partName): string
    {
        return hash('sha256', collect([$customer, $model, $partNumber, $partName])
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->implode('|'));
    }
}
