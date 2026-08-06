<?php

namespace App\Http\Controllers;

use App\Models\CogmSubmission;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\UnpricedPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $costing = \App\Models\CostingData::with('customer')->where('tracking_revision_id', $revision->id)->first();
        $rows = UnpricedPart::where('document_revision_id', $revision->id)->whereNull('resolved_at')->orderBy('part_number')->get();
        $customer = $costing?->customer;
        $project = $revision->project;
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
        foreach ($rows as $index => $row) {
            $line = 12 + $index;
            $values = [$row->id_code ?? '', $row->part_number, $row->notes ?? '', '', $row->part_name, $row->manual_price ?? $row->detected_price ?? 0, '', '', '', '', '', 0, 1];
            foreach ($values as $column => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column + 1) . $line, $value);
            }
        }
        $filename = now()->format('Y.m.d') . ' ' . $customerCode . ' - ' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($costing?->assy_no ?: $project?->part_number ?: 'NEW-PART')) . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) { (new Xlsx($spreadsheet))->save('php://output'); }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function updateUnpricedPartPrice(UpdateUnpricedPartPriceRequest $request, DocumentRevision $revision, TrackingDocumentUnpricedPartService $unpricedPartService)
    {
        return response()->json($unpricedPartService->updatePrice($revision, $request->validated()));
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
