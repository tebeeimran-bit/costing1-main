<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Material;
use App\Models\CostingData;
use App\Models\CostingExcelTemplate;
use App\Models\UnpricedPart;
use App\Models\DocumentRevision;
use App\Models\CycleTimeTemplate;
use App\Models\MaterialBreakdown;
use App\Models\Plant;
use App\Models\BusinessCategory;
use App\Models\Wire;
use App\Models\WireRate;
use App\Models\ExchangeRate;
use App\Models\Pic;
use App\Models\ProjectA00Form;
use App\Models\ProjectWorkflowTask;
use App\Http\Requests\StoreCostingRequest;
use App\Http\Requests\UpdateStatusProjectRequest;
use App\Services\Costing\CostingImportService;
use App\Services\Costing\CostingMaterialService;
use App\Services\Costing\CostingPersistenceService;
use App\Services\Costing\CostingResponseService;
use App\Services\Costing\CostingStatusService;
use App\Services\Costing\MissingProjectInformationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

trait 
HandlesCostingPersistence
{
    public function store(
        StoreCostingRequest $request,
        CostingImportService $importService,
        CostingMaterialService $materialService,
        CostingPersistenceService $persistenceService,
        CostingStatusService $statusService,
        CostingResponseService $responseService
    )
    {
        $updateSection = $request->resolvedUpdateSection();
        $importPartlistFileUploaded = $request->hasFile('import_partlist_file');
        $importCycleTimeFileUploaded = $request->hasFile('import_cycle_time_file');
        $validated = $request->validated();

        $persistenceService->applySelectedExchangeRate($request, $validated, $updateSection);

        $importRequested = $updateSection === 'material' && ($request->boolean('import_partlist') || $importPartlistFileUploaded);
        $importFromPartlist = $updateSection === 'material' && $importPartlistFileUploaded;
        $importedMaterialRows = [];
        $importCycleTimeRequested = $updateSection === 'cycle_time' && ($request->boolean('import_cycle_time') || $importCycleTimeFileUploaded);
        $importFromCycleTime = $updateSection === 'cycle_time' && $importCycleTimeFileUploaded;
        $importedCycleTimeRows = [];

        if ($importRequested) {
            $partlistImport = $importService->preparePartlistImport($validated, $request);
            if (!empty($partlistImport['error'])) {
                return back()->with('error', $partlistImport['error'])->withInput();
            }
            if (!empty($partlistImport['warning'])) {
                return back()->with('warning', $partlistImport['warning'])->withInput();
            }
            $importedMaterialRows = $partlistImport['rows'] ?? [];
        }

        if ($importCycleTimeRequested) {
            /*
             * Import UMH/Cycle Time wajib mengikuti format Excel aktual:
             * B17 ke bawah = NO
             * C17 ke bawah = PROCESS
             * F17 ke bawah = QTY
             * G17 ke bawah = TIME (HOUR)
             *
             * Jangan memakai parser service lama karena masih membaca format lain.
             */
            $cycleTimeImport = $this->loadCycleTimeRows($request->file('import_cycle_time_file'));

            if (!empty($cycleTimeImport['error'])) {
                return back()->with('error', $cycleTimeImport['error'])->withInput();
            }
            if (!empty($cycleTimeImport['warning'])) {
                return back()->with('warning', $cycleTimeImport['warning'])->withInput();
            }

            $importedCycleTimeRows = $cycleTimeImport['rows'] ?? [];
            $request->merge(['cycle_times' => $importedCycleTimeRows]);
        }

        DB::beginTransaction();
        try {
            $trackingRevisionId = $validated['tracking_revision_id'] ?? null;
            $editingSubmittedCogm = false;

            if ($trackingRevisionId) {
                $lockedStatus = DocumentRevision::query()
                    ->whereKey($trackingRevisionId)
                    ->value('status');

                $editingSubmittedCogm = $lockedStatus === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING
                    && $request->boolean('edit_submitted')
                    && in_array((string) ($request->user()->role ?? ''), ['admin', 'admin_costing', 'editor'], true);

                if (in_array($lockedStatus, [
                    DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
                    DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
                    DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
                ], true) && !$editingSubmittedCogm) {
                    DB::rollBack();

                    return back()
                        ->with('warning', 'Form costing sedang terkunci karena sudah masuk approval atau sudah dikirim ke Marketing.')
                        ->withInput($request->except(['import_partlist_file', 'import_cycle_time_file', 'import_cogm_file', 'import_umh_file']));
                }
            }

            $costingData = $persistenceService->resolveExistingCostingData($validated);
            $productId = $persistenceService->resolveProductId($request, $costingData);
            $costingData = $persistenceService->saveCostingData($request, $validated, $updateSection, $costingData, $productId);

            if ($trackingRevisionId) {
                ProjectWorkflowTask::where('document_revision_id', $trackingRevisionId)
                    ->where('stage', ProjectWorkflowTask::STAGE_COSTING)
                    ->where('status', ProjectWorkflowTask::STATUS_PENDING)
                    ->update([
                        'status' => ProjectWorkflowTask::STATUS_IN_PROGRESS,
                        'started_at' => now(),
                        'notes' => 'Form Costing sudah dibuat dan sedang diproses.',
                        'updated_at' => now(),
                    ]);
            }

            if ($trackingRevisionId && ($updateSection === '' || $updateSection === 'informasi_project')) {
                $revisionPayload = array_filter([
                    'pic_engineering' => $validated['pic_engineering'] ?? null,
                    'pic_marketing' => $validated['pic_marketing'] ?? null,
                ], fn ($value) => $value !== null && $value !== '');

                if ($revisionPayload !== []) {
                    DocumentRevision::whereKey($trackingRevisionId)->update($revisionPayload);
                }
            }

            $manualUnpricedPrices = $materialService->normalizeManualUnpricedPrices($request);
            $materialSyncResult = $materialService->syncMaterialBreakdowns($costingData, $request, [
                'import_from_partlist' => $importFromPartlist,
                'imported_material_rows' => $importedMaterialRows,
                'update_section' => $updateSection,
                'manual_unpriced_prices' => $manualUnpricedPrices,
            ]);
            $partAggregation = $materialSyncResult['part_aggregation'] ?? [];
            $shouldProcessMaterials = (bool) ($materialSyncResult['should_process_materials'] ?? false);

            if ($trackingRevisionId && !$importFromPartlist && !$editingSubmittedCogm) {
                if ($updateSection === 'unpriced_parts') {
                    $partAggregation = $statusService->syncUnpricedPartsFromBreakdowns((int) $trackingRevisionId, $costingData);
                }

                $statusService->updateTrackingRevisionStatus((int) $trackingRevisionId, $updateSection);
            }

            if ($shouldProcessMaterials) {
                $materialCostFromRequest = $this->parseNumericInput($request->input('material_cost', 0));
                $materialCost = $materialCostFromRequest > 0
                    ? $materialCostFromRequest
                    : $materialService->calculateMaterialCostFromBreakdowns(
                        (int) $costingData->id,
                        (float) ($costingData->exchange_rate_usd ?? 15500),
                        (float) ($costingData->exchange_rate_jpy ?? 103)
                    );

                $costingData->update([
                    'material_cost' => $materialCost,
                ]);
            }

            /*
             * Tombol utama "Simpan Data Costing" dan section Resume COGM harus
             * menyimpan angka yang sedang tampil di form. Ini mencegah nilai
             * Material Cost kembali membesar karena perbedaan format angka atau
             * kalkulasi ulang material lama yang belum normal.
             */
            if (($updateSection === '' || $updateSection === 'resume_cogm') && $request->has('material_cost')) {
                $costingData->update([
                    'material_cost' => $this->parseNumericInput($request->input('material_cost', 0)),
                ]);
            }

            DB::commit();

            /*
             * Tombol utama "Simpan Data Costing" dianggap sebagai final submit.
             * Setelah berhasil, arahkan user kembali ke halaman Project.
             * Tombol Update per section tetap kembali ke Form Costing agar user bisa lanjut edit section lain.
             */
            $redirectUrl = $updateSection === ''
                ? route('tracking-documents.index', [], false)
                : $responseService->buildRedirectUrl((int) $costingData->id, $trackingRevisionId ? (int) $trackingRevisionId : null);

            if ($editingSubmittedCogm && $updateSection !== '') {
                $redirectUrl .= (str_contains($redirectUrl, '?') ? '&' : '?').'edit_submitted=1';
            }

            $successMessage = $responseService->buildSuccessMessage(
                $updateSection,
                $importFromPartlist,
                count($importedMaterialRows),
                $importFromCycleTime,
                count($importedCycleTimeRows)
            );

            if ($updateSection === '') {
                $successMessage = $editingSubmittedCogm
                    ? 'Data costing berhasil disimpan. Gunakan Upload Update COGM untuk mengirim pembaruan resmi ke Marketing.'
                    : 'Data costing berhasil disimpan.';
            } elseif ($editingSubmittedCogm) {
                $successMessage = 'Perubahan berhasil disimpan sebagai draft Costing. Inbox Marketing belum diperbarui.';
            }

            if ($importFromPartlist) {
                session()->flash('just_imported_partlist', true);
            }

            if ($updateSection === 'unpriced_parts') {
                return redirect($redirectUrl)
                    ->with('success', $successMessage)
                    ->withInput($request->except(['import_partlist_file']));
            }

            session()->flash('success', $successMessage);

            if ($responseService->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => $redirectUrl,
                    'meta' => [
                        'part_aggregation_count' => count($partAggregation),
                        'costing_data_id' => (int) $costingData->id,
                    ],
                ]);
            }

            return redirect($redirectUrl);
        } catch (MissingProjectInformationException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CostingController@store error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'update_section' => $updateSection,
            ]);

            if ($responseService->shouldReturnJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function quickUpdateMaterial(Request $request)
    {
        /*
         * SAVE ONLY MATERIAL:
         * Simpan input material yang berubah saja.
         * Tidak hitung ulang material_cost / COGM / full price / unpriced parts di proses ini.
         * Tujuannya supaya tombol Simpan Material cepat dan tidak stuck.
         */
        $validated = $request->validate([
            'costing_data_id' => ['required', 'integer', 'exists:costing_data,id'],
            'materials_json' => ['required', 'string'],
        ]);

        $rows = json_decode((string) $validated['materials_json'], true);
        if (!is_array($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Payload Material tidak valid.',
            ], 422);
        }

        $costingData = CostingData::findOrFail((int) $validated['costing_data_id']);

        DB::beginTransaction();

        try {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns');
            $columnMap = array_fill_keys($columns, true);
            $now = now();
            $savedRows = 0;
            $placeholderMaterial = null;

            // Kunci data costing selama snapshot import disimpan agar request lain
            // tidak menimpa sebagian baris di tengah proses.
            CostingData::whereKey($costingData->id)->lockForUpdate()->firstOrFail();

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rowNo = (int) ($row['__row_no'] ?? 0);
                if ($rowNo <= 0) {
                    $rowNo = ((int) ($row['__row_index'] ?? 0)) + 1;
                }

                if ($rowNo <= 0) {
                    continue;
                }

                $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
                if ($currency !== '' && !in_array($currency, ['IDR', 'USD', 'JPY'], true)) {
                    $currency = '';
                }

                $cnType = strtoupper(trim((string) ($row['cn_type'] ?? '')));
                if ($cnType !== '' && !in_array($cnType, ['C', 'N', 'E'], true)) {
                    $cnType = '';
                }

                $amount1Raw = trim((string) ($row['amount1'] ?? ''));
                $unitPriceBasisRaw = trim((string) ($row['unit_price_basis'] ?? ''));
                $qtyMoqRaw = trim((string) ($row['qty_moq'] ?? ''));
                $importTaxRaw = trim((string) ($row['import_tax'] ?? ''));

                $payload = [
                    'row_no' => $rowNo,
                    'part_no' => trim((string) ($row['part_no'] ?? '')),
                    'id_code' => trim((string) ($row['id_code'] ?? '')) ?: null,
                    'part_name' => trim((string) ($row['part_name'] ?? '')),
                    'qty_req' => $this->parseQuantityValue($row['qty_req'] ?? 0),
                    'pro_code' => trim((string) ($row['pro_code'] ?? '')),
                    'amount1' => $amount1Raw === '' ? null : round($this->toFloatValue($amount1Raw), 6),
                    'unit_price_basis' => $unitPriceBasisRaw === '' ? null : round($this->toFloatValue($unitPriceBasisRaw), 6),
                    'unit_price_basis_text' => $unitPriceBasisRaw === '' ? null : $unitPriceBasisRaw,
                    'currency' => $currency === '' ? null : $currency,
                    'qty_moq' => $qtyMoqRaw === '' ? null : round($this->toFloatValue($qtyMoqRaw), 6),
                    'cn_type' => $cnType === '' ? null : $cnType,
                    'import_tax_percent' => $importTaxRaw === '' ? null : round($this->toFloatValue($importTaxRaw), 6),
                    'updated_at' => $now,
                ];

                if (isset($columnMap['unit'])) {
                    // Pertahankan isi file import apa adanya, termasuk unit kosong.
                    $payload['unit'] = trim((string) ($row['unit'] ?? ''));
                }

                if (isset($columnMap['supplier'])) {
                    $payload['supplier'] = trim((string) ($row['supplier'] ?? ''));
                }

                /*
                 * Save-only sengaja tidak update amount2/unit_price2/multiply_factor.
                 * Field-field itu akan dihitung ulang oleh proses Recalculate Material.
                 */
                $payload = array_intersect_key($payload, $columnMap);

                $breakdown = MaterialBreakdown::where('costing_data_id', $costingData->id)
                    ->where('row_no', $rowNo)
                    ->first();

                if (!$breakdown) {
                    $breakdown = MaterialBreakdown::where('costing_data_id', $costingData->id)
                        ->orderBy('id')
                        ->offset($rowNo - 1)
                        ->first();
                }

                if ($breakdown) {
                    $breakdown->fill($payload);
                    $breakdown->save();
                } else {
                    if (!$placeholderMaterial) {
                        $placeholderMaterial = Material::firstOrCreate(
                            ['material_code' => '__PLACEHOLDER__'],
                            [
                                'material_description' => null,
                                'base_uom' => 'PCS',
                                'currency' => 'IDR',
                                'price' => 0,
                            ]
                        );
                    }

                    $payload['costing_data_id'] = $costingData->id;
                    $payload['material_id'] = $placeholderMaterial->id;
                    MaterialBreakdown::create($payload);
                }

                $savedRows++;
            }

            if ($savedRows !== count($rows)) {
                throw new \RuntimeException("Penyimpanan Material tidak lengkap ({$savedRows}/" . count($rows) . ' baris).');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Material berhasil disimpan. Silakan klik Hitung Ulang COGM untuk memperbarui total.',
                'save_only' => true,
                'needs_recalculate' => true,
                'sent_rows' => count($rows),
                'saved_rows' => $savedRows,
                'updated_rows' => $savedRows,
                'version' => 'material-snapshot-save-v2',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('CostingController@quickUpdateMaterial save-only error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan Material: ' . $e->getMessage(),
            ], 500);
        }
    }

}
