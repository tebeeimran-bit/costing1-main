<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Customer;
use App\Models\CogmSubmission;
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
HandlesCostingMarketing
{
    public function marketingCostingView(Request $request, \App\Models\CogmSubmission $submission, CostingImportService $importService)
    {
        $role = (string) ($request->user()->role ?? '');
        abort_unless(in_array($role, ['admin', 'admin_costing', 'marketing', 'coordinator_costing'], true), 403);
        $this->authorizeMarketingSubmissionAccess($request, $submission);

        $request->query->set('tracking_revision_id', $submission->document_revision_id);
        $request->query->set('cogm_submission_id', $submission->id);
        $request->query->set('view_only', '1');

        return $this->form($request, $importService);
    }

    public function downloadCostingEdit(Request $request, DocumentRevision $revision)
    {
        $role = (string) ($request->user()->role ?? '');
        abort_unless(in_array($role, ['admin', 'admin_costing', 'marketing', 'coordinator_costing'], true), 403);
        if ($role === 'marketing') {
            abort_unless(
                mb_strtolower(trim((string) $revision->pic_marketing))
                    === mb_strtolower(trim((string) $request->user()->name)),
                403,
                'Dokumen COGM ini ditujukan untuk PIC Marketing lain.'
            );
        }
        abort_unless($revision->costing_edit_file_path && Storage::disk('local')->exists($revision->costing_edit_file_path), 404);

        return Storage::disk('local')->download(
            $revision->costing_edit_file_path,
            $revision->costing_edit_original_name ?: 'Import-Hasil-Edit.xlsx'
        );
    }

    public function downloadExportedCogm(Request $request, DocumentRevision $revision)
    {
        $role = (string) ($request->user()->role ?? '');
        abort_unless(in_array($role, ['admin', 'admin_costing', 'marketing', 'coordinator_costing', 'editor'], true), 403);

        $path = $revision->cogm_export_file_path ?: $revision->cogm_import_file_path;
        $name = $revision->cogm_export_original_name ?: $revision->cogm_import_original_name ?: 'COGM.xlsx';
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'File Excel COGM belum tersedia. Lakukan Export COGM dari Form Costing terlebih dahulu.');

        return Storage::disk('local')->download($path, $name);
    }

    public function downloadImportedCogm(Request $request, CogmSubmission $submission)
    {
        $role = (string) ($request->user()->role ?? '');
        abort_unless(in_array($role, ['admin', 'admin_costing', 'marketing', 'coordinator_costing'], true), 403);
        $this->authorizeMarketingSubmissionAccess($request, $submission);

        $revision = $submission->revision;
        abort_unless(
            $revision?->cogm_import_file_path
                && Storage::disk('local')->exists($revision->cogm_import_file_path),
            404,
            'File Import COGM tidak tersedia.'
        );

        return Storage::disk('local')->download(
            $revision->cogm_import_file_path,
            $revision->cogm_import_original_name ?: 'Import-COGM.xlsx'
        );
    }

    private function authorizeMarketingSubmissionAccess(Request $request, CogmSubmission $submission): void
    {
        if ((string) ($request->user()->role ?? '') !== 'marketing') {
            return;
        }

        abort_unless(
            mb_strtolower(trim((string) $submission->pic_marketing))
                === mb_strtolower(trim((string) $request->user()->name)),
            403,
            'COGM ini ditujukan untuk PIC Marketing lain.'
        );
    }

}
