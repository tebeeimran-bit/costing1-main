<?php

namespace App\Http\Controllers;

use App\Models\CogmSubmission;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\UnpricedPart;
use App\Models\ProjectDocumentRevision;
use App\Models\ProjectWorkflowTask;
use App\Models\User;
use App\Notifications\CostingGroupChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\BulkDeleteUnpricedPartsRequest;
use App\Http\Requests\DeleteUnpricedPartRequest;
use App\Http\Requests\RestoreUnpricedPartRequest;
use App\Http\Requests\StoreDocumentReceiptRequest;
use App\Http\Requests\UpdateTrackingFilesRequest;
use App\Http\Requests\UpdateTrackingProjectInfoRequest;
use App\Http\Requests\UpdateUnpricedPartPriceRequest;
use App\Services\TrackingDocument\TrackingDocumentFileService;
use App\Services\TrackingDocument\TrackingDocumentProjectService;
use App\Services\TrackingDocument\TrackingDocumentSharedDataService;
use App\Services\TrackingDocument\TrackingDocumentUnpricedPartService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TrackingDocumentController extends Controller
{
    public function create(TrackingDocumentSharedDataService $sharedDataService)
    {
        return view('tracking-documents.create', $sharedDataService->getFormOptions());
    }

    public function storeReceipt(StoreDocumentReceiptRequest $request, TrackingDocumentProjectService $projectService)
    {
        $projectService->createReceipt($request->validated(), $request);

        if ($request->boolean('embedded')) {
            return view('tracking-documents.created');
        }

        return redirect()->route('project')
            ->with('success', 'Project baru berhasil dibuat.');
    }

    public function markCogmGenerated(DocumentRevision $revision)
    {
        $hasOpenUnpriced = UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->exists();

        if ($hasOpenUnpriced) {
            $revision->update([
                'status' => DocumentRevision::STATUS_PENDING_PRICING,
            ]);

            return redirect()->back()
                ->with('warning', 'Masih ada part tanpa harga. Status tetap Draft / Pending Pricing.');
        }

        if (in_array($revision->status, [
            DocumentRevision::STATUS_PENDING_FORM_INPUT,
            DocumentRevision::STATUS_PENDING_PRICING,
        ], true)) {
            $revision->update([
                'status' => DocumentRevision::STATUS_COGM_GENERATED,
                'cogm_generated_at' => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Status berhasil diubah ke COGM Generated.');
    }

    public function processToFormInput(DocumentRevision $revision)
    {
        $revision->update([
            'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
            'cogm_generated_at' => null,
        ]);

        return redirect()->to(route('form', ['tracking_revision_id' => $revision->id], false));
    }

    public function submitCogm(Request $request, DocumentRevision $revision)
    {
        $hasOpenUnpriced = UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->exists();

        if ($hasOpenUnpriced) {
            return redirect()->back()
                ->with('warning', 'Submit COGM ditolak karena masih ada part tanpa harga pada revisi ini.');
        }

        $validated = $request->validate([
            'pic_marketing' => 'required|string|max:255',
            'cogm_value' => 'nullable|numeric',
            'submitted_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        CogmSubmission::create([
            'document_revision_id' => $revision->id,
            'submitted_at' => now(),
            'pic_marketing' => $validated['pic_marketing'],
            'cogm_value' => $validated['cogm_value'] ?? null,
            'submitted_by' => $validated['submitted_by'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $revision->update([
            'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
            'pic_marketing' => $validated['pic_marketing'],
        ]);

        return redirect()->back()
            ->with('success', 'COGM berhasil disubmit ke Marketing.');
    }

    public function updateFiles(UpdateTrackingFilesRequest $request, DocumentRevision $revision, TrackingDocumentFileService $fileService)
    {
        if (!$request->hasFile('partlist_file') && !$request->hasFile('umh_file')) {
            return redirect()->back()->with('warning', 'Pilih minimal satu file (Partlist atau UMH) untuk diupdate.');
        }

        $validated = $request->validated();

        $updatedRevision = DB::transaction(function () use ($request, $revision, $fileService, $validated) {
            $targetRevision = DocumentRevision::query()
                ->whereKey($revision->id)
                ->lockForUpdate()
                ->firstOrFail();

            $partlistPath = $targetRevision->partlist_file_path;
            $partlistOriginalName = $targetRevision->partlist_original_name;
            $umhPath = $targetRevision->umh_file_path;
            $umhOriginalName = $targetRevision->umh_original_name;

            if ($request->hasFile('partlist_file')) {
                $storedPartlist = $fileService->replaceUploadedFile($targetRevision, $request->file('partlist_file'), 'partlist');
                $partlistPath = $storedPartlist['path'];
                $partlistOriginalName = $storedPartlist['name'];
            }

            if ($request->hasFile('umh_file')) {
                $storedUmh = $fileService->replaceUploadedFile($targetRevision, $request->file('umh_file'), 'umh');
                $umhPath = $storedUmh['path'];
                $umhOriginalName = $storedUmh['name'];
            }

            $targetRevision->update([
                'partlist_original_name' => $partlistOriginalName,
                'partlist_file_path' => $partlistPath,
                'partlist_update_count' => $request->hasFile('partlist_file')
                    ? ((int) ($targetRevision->partlist_update_count ?? 0) + 1)
                    : (int) ($targetRevision->partlist_update_count ?? 0),
                'partlist_updated_at' => $request->hasFile('partlist_file') ? now() : $targetRevision->partlist_updated_at,
                'umh_original_name' => $umhOriginalName,
                'umh_file_path' => $umhPath,
                'umh_update_count' => $request->hasFile('umh_file')
                    ? ((int) ($targetRevision->umh_update_count ?? 0) + 1)
                    : (int) ($targetRevision->umh_update_count ?? 0),
                'umh_updated_at' => $request->hasFile('umh_file') ? now() : $targetRevision->umh_updated_at,
                'change_remark' => trim((string) ($validated['change_remark'] ?? '')) !== ''
                    ? trim((string) $validated['change_remark'])
                    : '-',
            ]);

            return $targetRevision->fresh();
        });

        return redirect()->back()->with('success', 'Dokumen pada ' . $updatedRevision->version_label . ' berhasil diperbarui.');
    }

    public function addVersion(DocumentRevision $revision, TrackingDocumentProjectService $projectService)
    {
        $newRevision = $projectService->addVersion($revision);

        return redirect()->back()->with('success', 'Versi baru ' . $newRevision->version_label . ' berhasil ditambahkan.');
    }

    public function deleteVersion(DocumentRevision $revision, TrackingDocumentProjectService $projectService)
    {
        $result = $projectService->deleteVersion($revision);

        if (!($result['deleted'] ?? false)) {
            if (($result['reason'] ?? '') === 'last_version') {
                return redirect()->back()->with('warning', 'Versi tidak bisa dihapus karena project harus memiliki minimal satu versi.');
            }

            return redirect()->back()->with('warning', 'Versi tidak ditemukan atau sudah terhapus.');
        }

        return redirect()->back()->with('success', 'Versi ' . ($result['version_label'] ?? '') . ' berhasil dihapus.');
    }

    public function updateProjectInfo(UpdateTrackingProjectInfoRequest $request, DocumentProject $project, TrackingDocumentProjectService $projectService)
    {
        $result = $projectService->updateProjectInfo($project, $request->validated(), $request);

        if (!($result['updated'] ?? false) && ($result['reason'] ?? '') === 'duplicate') {
            return redirect()->back()->with('warning', 'Informasi project sama persis dengan project lain yang sudah ada.');
        }

        return redirect()->back()->with('success', 'Informasi project berhasil diperbarui.');
    }

    public function destroyProject(DocumentProject $project, TrackingDocumentFileService $fileService)
    {
        DB::transaction(function () use ($project, $fileService) {
            $fileService->deletePaths($fileService->collectProjectFilePaths($project));
            $project->delete();
        });

        return redirect()->back()->with('success', 'Semua data project berhasil dihapus.');
    }

    public function exportUnpricedParts(DocumentRevision $revision, string $format)
    {
        $rows = UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->orderBy('part_number')
            ->get();

        if ($format === 'excel') {
            $filename = 'unpriced-parts-' . $revision->id . '-v' . $revision->version_number . '.csv';

            $csv = collect([
                ['Part Number', 'Part Name', 'Detected Price'],
            ])->concat($rows->map(function ($item) {
                return [
                    $item->part_number,
                    $item->part_name,
                    (string) ($item->detected_price ?? 0),
                ];
            }))->map(function ($line) {
                return collect($line)->map(function ($cell) {
                    $escaped = str_replace('"', '""', (string) $cell);
                    return '"' . $escaped . '"';
                })->implode(',');
            })->implode("\n");

            return response($csv)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename=' . $filename);
        }

        $html = view('tracking-documents.unpriced-parts-pdf', [
            'revision' => $revision,
            'rows' => $rows,
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function exportNewPartRequest(DocumentRevision $revision)
    {
        $revision->load(['project.a00Form', 'project.revisions', 'project.product']);
        $costing = \App\Models\CostingData::with(['customer', 'materialBreakdowns'])->where('tracking_revision_id', $revision->id)->first();
        $editedMaterialRows = $this->newPartRowsFromCostingEdit($revision);
        if ($editedMaterialRows === null && $costing) {
            $editedMaterialRows = $costing->materialBreakdowns
                ->filter(fn ($row) => $row->amount1 === null || (float) $row->amount1 <= 0)
                ->map(fn ($row) => [
                    'item_code' => trim((string) $row->part_no),
                    'id_code' => trim((string) $row->id_code),
                    'description' => trim((string) $row->part_name),
                ])
                ->filter(fn ($row) => $row['item_code'] !== '')
                ->values()->all();
        }
        if ($editedMaterialRows !== null) {
            $this->syncUnpricedPartsFromCostingEdit($revision, $costing, $editedMaterialRows);
            if ($editedMaterialRows !== []) $revision->touch();
        }
        $revision->update([
            'new_part_request_exported_at' => now(),
            'new_part_request_exported_by_id' => auth()->id(),
        ]);
        $rows = UnpricedPart::where('document_revision_id', $revision->id)->whereNull('resolved_at')->orderBy('part_number')->get();
        $project = $revision->project;
        $customer = $costing?->customer;
        if (!$customer && $project?->customer) {
            $customer = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $project->customer))])->first();
        }
        $customerCode = strtoupper(trim((string) ($customer?->code ?: $project?->customer ?: 'CUSTOMER')));
        $customerName = (string) ($customer?->name ?: $project?->customer ?: '');
        $sop = $project?->a00Form?->sop_mp_date?->format('d/m/Y') ?: 'TBA';
        $templatePath = storage_path('app/templates/new-part-request-template.xlsx');
        if (!is_file($templatePath)) {
            abort(500, 'Template New Part Request tidak ditemukan: ' . $templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $customerCode), 0, 31) ?: 'CUSTOMER');
        $sheet->setCellValue('K2', 'Date :'); $sheet->setCellValue('L2', now()->format('d/m/Y'));
        $sheet->setCellValue('D3', 'CUSTOMER'); $sheet->setCellValue('E3', $customerName); $sheet->setCellValue('K3', 'Req. No :');
        $sheet->setCellValue('D4', 'END CUSTOMER'); $sheet->setCellValue('E4', $customerName);
        $sheet->setCellValue('D5', 'PROJECT MODEL'); $sheet->setCellValue('E5', (string) ($project?->model ?: $costing?->model ?: ''));
        $sheet->setCellValue('D6', 'APPLICATION'); $sheet->setCellValue('E6', (string) ($costing?->assy_name ?: $project?->part_name ?: ''));
        $sheet->setCellValue('D7', 'MASS PRO DATE'); $sheet->setCellValue('E7', $sop);
        $sheet->setCellValue('D8', 'VOLUME/MONTH'); $sheet->setCellValue('E8', (float) ($costing?->forecast ?? 0));
        if ($editedMaterialRows !== null) {
            foreach ($editedMaterialRows as $index => $row) {
                $line = 12 + $index;
                $sheet->setCellValue("A{$line}", $row['id_code']);
                $sheet->setCellValue("B{$line}", null);
                $sheet->setCellValue("C{$line}", null);
                $sheet->setCellValue("D{$line}", $row['item_code']);
                $sheet->setCellValue("E{$line}", $row['description']);
                $sheet->setCellValue("F{$line}", null);
                $sheet->setCellValue("L{$line}", null);
                $sheet->setCellValue("M{$line}", null);
            }
        } else {
            foreach ($rows as $index => $row) {
                $line = 12 + $index;
                // Keep the exported workbook aligned with the agreed template:
                // A = ID code, B-C = blank, D = customer part number,
                // E = description. Price, Add Cost, and Qty are user inputs.
                $values = [$row->id_code ?? '', '', '', $row->part_number, $row->part_name, '', '', '', '', '', '', '', ''];
                foreach ($values as $column => $value) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($column + 1) . $line, $value);
                }
            }
        }
        $filename = now()->format('Y.m.d') . ' ' . $customerCode . ' - ' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($costing?->assy_no ?: $project?->part_number ?: 'NEW-PART')) . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) { (new Xlsx($spreadsheet))->save('php://output'); }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function syncNewPartRequestRows(Request $request, DocumentRevision $revision)
    {
        $data = $request->validate([
            'materials' => ['required', 'array', 'max:10000'],
            'materials.*.part_no' => ['nullable', 'string', 'max:255'],
            'materials.*.id_code' => ['nullable', 'string', 'max:255'],
            'materials.*.part_name' => ['nullable', 'string', 'max:500'],
            'materials.*.amount1' => ['nullable'],
        ]);

        $costing = \App\Models\CostingData::where('tracking_revision_id', $revision->id)->latest('id')->first();
        $rows = collect($data['materials'])
            ->filter(function ($row) {
                $partNumber = trim((string) ($row['part_no'] ?? ''));
                $price = $this->newPartRequestNumber($row['amount1'] ?? null);
                return $partNumber !== '' && ($price === null || $price <= 0);
            })
            ->map(fn ($row) => [
                'item_code' => trim((string) ($row['part_no'] ?? '')),
                'id_code' => trim((string) ($row['id_code'] ?? '')),
                'description' => trim((string) ($row['part_name'] ?? '')),
            ])->values()->all();

        $this->syncUnpricedPartsFromCostingEdit($revision, $costing, $rows);
        $revision->update([
            'new_part_request_exported_at' => now(),
            'new_part_request_exported_by_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'count' => count($rows),
            'message' => count($rows).' part tanpa harga berhasil dimasukkan ke Inbox New Part Request.',
        ]);
    }

    public function importNewPartRequest(Request $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        $validated = $request->validate([
            'new_part_request_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        try {
            $reader = IOFactory::createReaderForFile($validated['new_part_request_file']->getRealPath());
            $reader->setReadDataOnly(true);
            $workbook = $reader->load($validated['new_part_request_file']->getRealPath());
            $sheet = $workbook->getActiveSheet();
            $updated = 0;
            $notFound = [];
            $emptyStreak = 0;

            DB::transaction(function () use ($sheet, $revision, $request, &$updated, &$notFound, &$emptyStreak) {
                $highestRow = max(12, (int) $sheet->getHighestDataRow());
                for ($row = 12; $row <= $highestRow; $row++) {
                    $idCode = trim((string) $sheet->getCell("A{$row}")->getFormattedValue());
                    $partNumber = trim((string) $sheet->getCell("D{$row}")->getFormattedValue());
                    $usableIdCode = !in_array($idCode, ['', '-'], true) ? $idCode : '';
                    if ($partNumber === '' && $usableIdCode === '') {
                        $emptyStreak++;
                        if ($emptyStreak >= 20) break;
                        continue;
                    }
                    $emptyStreak = 0;

                    $item = UnpricedPart::where('document_revision_id', $revision->id)
                        ->whereNull('resolved_at')
                        ->where(function ($query) use ($partNumber, $usableIdCode) {
                            if ($partNumber !== '') {
                                $query->whereRaw('LOWER(part_number) = ?', [mb_strtolower($partNumber)]);
                            }
                            if ($usableIdCode !== '') {
                                $method = $partNumber !== '' ? 'orWhereRaw' : 'whereRaw';
                                $query->{$method}('LOWER(id_code) = ?', [mb_strtolower($usableIdCode)]);
                            }
                        })
                        ->first();

                    if (!$item) {
                        $notFound[] = $partNumber !== '' ? $partNumber : $usableIdCode;
                        continue;
                    }

                    $item->update([
                        'id_code' => $usableIdCode !== '' ? $usableIdCode : $item->id_code,
                        'detected_price' => $this->newPartRequestNumber($sheet->getCell("F{$row}")->getFormattedValue()),
                        'purchase_unit' => trim((string) $sheet->getCell("G{$row}")->getFormattedValue()) ?: null,
                        'currency' => strtoupper(trim((string) $sheet->getCell("H{$row}")->getFormattedValue())) ?: null,
                        'moq' => $this->newPartRequestNumber($sheet->getCell("I{$row}")->getFormattedValue()),
                        'cn_type' => strtoupper(trim((string) $sheet->getCell("J{$row}")->getFormattedValue())) ?: null,
                        'maker' => trim((string) $sheet->getCell("K{$row}")->getFormattedValue()) ?: null,
                        'add_cost_percent' => $this->newPartRequestNumber($sheet->getCell("L{$row}")->getFormattedValue()),
                        'new_part_price_imported_at' => now(),
                        'new_part_price_imported_by_id' => $request->user()->id,
                        'notes' => 'Harga diimport dari Form New Part Request.',
                    ]);
                    $updated++;
                }
            });

            $submitResult = $unpricedPartService->submitImportedPrices($revision, (int) $request->user()->id);
            $uploadedFile = $validated['new_part_request_file'];
            $storedPath = $uploadedFile->store('workflow/costing-revisions/'.$revision->id.'/price');
            $costingTask = $revision->workflowTasks()->where('stage', ProjectWorkflowTask::STAGE_COSTING)->latest('id')->first();
            ProjectDocumentRevision::create([
                'document_project_id' => $revision->document_project_id,
                'document_revision_id' => $revision->id,
                'workflow_task_id' => $costingTask?->id,
                'revision_type' => 'price',
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_path' => $storedPath,
                'description' => $submitResult['submitted'].' harga kosong diperbarui melalui Inbox New Part Request.',
                'uploaded_by' => $request->user()->id,
            ]);

            $submission = CogmSubmission::where('document_revision_id', $revision->id)->latest('submitted_at')->first();
            if ($submission) {
                $submission->update([
                    'update_count' => ((int) $submission->update_count) + 1,
                    'last_updated_by' => $request->user()->name,
                    'last_updated_at' => now(),
                ]);
                $submission->events()->create([
                    'user_id'=>$request->user()->id,'event_type'=>'price_updated','source'=>'new_part_request',
                    'title'=>'Harga New Part diperbarui',
                    'description'=>$submitResult['submitted'].' harga part diperbarui melalui Inbox New Part Request.',
                    'cogm_value'=>$submission->cogm_value,
                ]);
            }

            $revision->loadMissing('project');
            $projectNumber = $revision->project?->part_number ?: 'Project';
            $costingRecipients = User::whereIn('role', ['admin', 'admin_costing', 'coordinator_costing', 'editor'])->get();
            $costingPayload = [
                'event' => 'price_updated', 'title' => 'Update Harga New Part Request',
                'message' => $projectNumber.' telah menerima update harga untuk '.$submitResult['submitted'].' part.',
                'a00_number' => $projectNumber, 'url' => route('costing.inbox', absolute: false),
            ];
            $costingRecipients->each->notify(new CostingGroupChanged($costingPayload));

            if ($submission) {
                $picName = mb_strtolower(trim((string) $submission->pic_marketing));
                $marketingRecipients = User::where('role', 'marketing')
                    ->when($picName !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [$picName]))
                    ->get();
                $marketingPayload = [
                    'event' => 'price_updated', 'title' => 'Update Harga Form Costing',
                    'message' => 'Harga '.$projectNumber.' telah diperbarui melalui New Part Request.',
                    'a00_number' => $projectNumber, 'url' => route('marketing.cogm-inbox', absolute: false),
                ];
                $marketingRecipients->each->notify(new CostingGroupChanged($marketingPayload));
            }

            return response()->json([
                'success' => true,
                'message' => $submitResult['message'].($submission ? ' Inbox Marketing juga telah diperbarui.' : ''),
                'updated' => $updated,
                'submitted' => $submitResult['submitted'],
                'not_found' => $notFound,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'File New Part Request tidak dapat dibaca: ' . $exception->getMessage(),
            ], 422);
        }
    }

    private function newPartRequestNumber(mixed $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-') return null;
        $raw = preg_replace('/\s+/', '', $raw);
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }
        return is_numeric($raw) ? (float) $raw : null;
    }

    private function syncUnpricedPartsFromCostingEdit(DocumentRevision $revision, ?\App\Models\CostingData $costing, array $rows): void
    {
        DB::transaction(function () use ($revision, $costing, $rows) {
            $currentPartNumbers = [];
            foreach ($rows as $row) {
                $partNumber = trim((string) ($row['item_code'] ?? ''));
                if ($partNumber === '') {
                    continue;
                }
                $currentPartNumbers[] = $partNumber;
                $unpricedPart = UnpricedPart::firstOrCreate(
                    [
                        'document_revision_id' => $revision->id,
                        'part_number' => $partNumber,
                        'resolved_at' => null,
                    ],
                    [
                        'costing_data_id' => $costing?->id,
                        'id_code' => trim((string) ($row['id_code'] ?? '')) ?: null,
                        'part_name' => trim((string) ($row['description'] ?? '')) ?: null,
                        'detected_price' => null,
                        'manual_price' => null,
                        'resolution_source' => null,
                        'notes' => 'Dideteksi dari Amount 1 kosong pada file Import Hasil Edit.',
                    ]
                );

                // Export ulang template tidak boleh menghapus harga F-L yang sudah
                // diimport dan sedang menunggu Submit.
                if (!$unpricedPart->wasRecentlyCreated) {
                    $unpricedPart->update([
                        'costing_data_id' => $costing?->id ?? $unpricedPart->costing_data_id,
                        'id_code' => trim((string) ($row['id_code'] ?? '')) ?: $unpricedPart->id_code,
                        'part_name' => trim((string) ($row['description'] ?? '')) ?: $unpricedPart->part_name,
                    ]);
                }
            }

            $staleQuery = UnpricedPart::where('document_revision_id', $revision->id)->whereNull('resolved_at');
            if ($currentPartNumbers !== []) {
                $staleQuery->whereNotIn('part_number', array_values(array_unique($currentPartNumbers)));
            }
            $staleQuery->update([
                'resolved_at' => now(),
                'resolution_source' => 'not_in_latest_costing_edit',
            ]);
        });
    }

    /**
     * Read new-part rows directly from the latest imported costing-edit workbook.
     * A valid workbook returns an array (including an empty one); null means the
     * stored workbook is unavailable/invalid and the caller should use its fallback.
     */
    private function newPartRowsFromCostingEdit(DocumentRevision $revision): ?array
    {
        $path = trim((string) $revision->costing_edit_file_path);
        if ($path === '' || !Storage::disk('local')->exists($path)) {
            return null;
        }

        if (!class_exists(\ZipArchive::class) || !class_exists(\XMLReader::class)) {
            return null;
        }

        $archive = new \ZipArchive();
        $archiveOpened = false;
        try {
            $filePath = Storage::disk('local')->path($path);
            if ($archive->open($filePath) !== true) {
                return null;
            }
            $archiveOpened = true;

            $workbookXml = $archive->getFromName('xl/workbook.xml');
            $relationsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relationsXml === false) {
                return null;
            }

            $workbook = @simplexml_load_string($workbookXml);
            $relations = @simplexml_load_string($relationsXml);
            if (!$workbook || !$relations) {
                return null;
            }

            $relationshipTargets = [];
            foreach ($relations->Relationship as $relationship) {
                $relationshipTargets[(string) $relationship['Id']] = (string) $relationship['Target'];
            }

            $sheetEntry = null;
            $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            foreach ($workbook->sheets->sheet ?? [] as $sheet) {
                if (strcasecmp(trim((string) $sheet['name']), 'Material Cost') !== 0) {
                    continue;
                }
                $attributes = $sheet->attributes($relationshipNamespace);
                $target = $relationshipTargets[(string) ($attributes['id'] ?? '')] ?? '';
                if ($target !== '') {
                    $sheetEntry = str_starts_with($target, '/')
                        ? ltrim($target, '/')
                        : 'xl/' . ltrim($target, '/');
                }
                break;
            }
            if (!$sheetEntry || $archive->locateName($sheetEntry) === false) {
                return null;
            }

            $rawRows = [];
            $neededSharedStrings = [];
            $sheetReader = new \XMLReader();
            if (!$sheetReader->open('zip://' . str_replace('\\', '/', $filePath) . '#' . $sheetEntry, null, LIBXML_NONET | LIBXML_COMPACT)) {
                return null;
            }

            while ($sheetReader->read()) {
                if ($sheetReader->nodeType !== \XMLReader::ELEMENT || $sheetReader->localName !== 'c') {
                    continue;
                }
                $reference = strtoupper((string) $sheetReader->getAttribute('r'));
                if (!preg_match('/^([DFGL])(\d+)$/', $reference, $matches) || (int) $matches[2] < 18) {
                    continue;
                }

                $cell = @simplexml_load_string($sheetReader->readOuterXml());
                if (!$cell) {
                    continue;
                }
                $type = (string) ($cell['t'] ?? '');
                if ($type === 'inlineStr') {
                    $value = '';
                    foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                        $value .= (string) $textNode;
                    }
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $rowNumber = (int) $matches[2];
                $column = $matches[1];
                if ($type === 's' && ctype_digit($value)) {
                    $sharedIndex = (int) $value;
                    $rawRows[$rowNumber][$column] = ['shared' => $sharedIndex];
                    $neededSharedStrings[$sharedIndex] = true;
                } else {
                    $rawRows[$rowNumber][$column] = ['value' => $value];
                }
            }
            $sheetReader->close();

            $sharedStrings = [];
            if ($neededSharedStrings && $archive->locateName('xl/sharedStrings.xml') !== false) {
                $sharedReader = new \XMLReader();
                if ($sharedReader->open('zip://' . str_replace('\\', '/', $filePath) . '#xl/sharedStrings.xml', null, LIBXML_NONET | LIBXML_COMPACT)) {
                    $sharedIndex = 0;
                    while ($sharedReader->read()) {
                        if ($sharedReader->nodeType !== \XMLReader::ELEMENT || $sharedReader->localName !== 'si') {
                            continue;
                        }
                        if (isset($neededSharedStrings[$sharedIndex])) {
                            $sharedNode = @simplexml_load_string($sharedReader->readOuterXml());
                            $text = '';
                            foreach ($sharedNode?->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                                $text .= (string) $textNode;
                            }
                            $sharedStrings[$sharedIndex] = $text;
                        }
                        $sharedIndex++;
                    }
                    $sharedReader->close();
                }
            }

            ksort($rawRows);
            $rows = [];
            foreach ($rawRows as $cells) {
                $value = static function (array $cell = []) use ($sharedStrings): string {
                    return trim(isset($cell['shared'])
                        ? (string) ($sharedStrings[$cell['shared']] ?? '')
                        : (string) ($cell['value'] ?? ''));
                };
                $itemCode = $value($cells['D'] ?? []);
                $amount = $value($cells['L'] ?? []);
                if ($itemCode === '' || $amount !== '') {
                    continue;
                }

                $rows[] = [
                    'item_code' => $itemCode,
                    'id_code' => $value($cells['F'] ?? []),
                    'description' => $value($cells['G'] ?? []),
                ];
            }

            return $rows;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        } finally {
            if ($archiveOpened) {
                $archive->close();
            }
        }
    }

    public function updateUnpricedPartPrice(UpdateUnpricedPartPriceRequest $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        try {
            return response()->json($unpricedPartService->updatePrice(
                $revision,
                $request->validated() + ['_actor_user_id' => $request->user()->id]
            ));
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function deleteUnpricedPart(DeleteUnpricedPartRequest $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        return response()->json(
            $unpricedPartService->delete($revision, (string) $request->validated()['part_number'])
        );
    }

    public function bulkDeleteUnpricedParts(BulkDeleteUnpricedPartsRequest $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        return response()->json(
            $unpricedPartService->bulkDelete($revision, $request->validated()['part_numbers'])
        );
    }

    public function restoreUnpricedPart(RestoreUnpricedPartRequest $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        return response()->json(
            $unpricedPartService->restore($revision, (string) $request->validated()['part_number'])
        );
    }

    public function download(DocumentRevision $revision, string $type, TrackingDocumentFileService $fileService)
    {
        return $fileService->downloadRevisionFile($revision, $type);
    }

    public function viewDocument(DocumentRevision $revision, string $type, TrackingDocumentFileService $fileService)
    {
        return $fileService->inlineRevisionFile($revision, $type);
    }

}
