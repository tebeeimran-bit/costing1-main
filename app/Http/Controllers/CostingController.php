<?php

namespace App\Http\Controllers;

use App\Models\DocumentRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CostingController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesCostingMarketing;
    use \App\Http\Controllers\Concerns\HandlesCostingDashboard;
    use \App\Http\Controllers\Concerns\HandlesCostingComparison;
    use \App\Http\Controllers\Concerns\HandlesCostingForm;
    use \App\Http\Controllers\Concerns\HandlesCostingPersistence;
    use \App\Http\Controllers\Concerns\HandlesMaterialEditor;
    use \App\Http\Controllers\Concerns\HandlesCogmImport;
    use \App\Http\Controllers\Concerns\HandlesCostingFileImports;
    use \App\Http\Controllers\Concerns\HandlesPartlistParsing;

    public function updateStatusProject(Request $request, $revisionId)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:A00,A04,A05'],
        ]);

        $revision = DocumentRevision::with('project')->findOrFail($revisionId);
        $status = (string) $validated['status'];

        /*
         * Business rule final:
         * - A00 boleh langsung disimpan.
         * - A04/A05 TIDAK langsung disimpan dari dropdown dashboard.
         * - A04/A05 baru disimpan setelah user upload dokumen wajib di halaman Project Document.
         * - Jika modal Project Document dibatalkan/ditutup, status tetap status sebelumnya.
         */
        if ($status === 'A00') {
            $revision->forceFill([
                'a00' => 'ada',
                'a04' => 'belum_ada',
                'a05' => 'belum_ada',
            ])->save();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => $status,
                    'revision_id' => $revision->id,
                    'a00' => $revision->a00,
                    'a04' => $revision->a04,
                    'a05' => $revision->a05,
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Status project berhasil diperbarui menjadi A00.');
        }

        $projectLabel = trim(implode(' - ', array_filter([
            $revision->project?->customer,
            $revision->project?->model,
            $revision->project?->part_number,
        ]))) ?: '-';

        $redirectUrl = Route::has('database.project-documents')
            ? route('database.project-documents', [], false)
            : url()->previous();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'pending_document_upload' => true,
                'status' => $status,
                'revision_id' => $revision->id,
                'redirect' => $redirectUrl,
                'message' => 'Silakan upload dokumen ' . $status . ' terlebih dahulu. Status belum berubah sampai dokumen disimpan.',
            ]);
        }

        return redirect($redirectUrl)
            ->with('warning', 'Silakan upload dokumen ' . $status . ' terlebih dahulu. Status belum berubah sampai dokumen disimpan.')
            ->with('open_document_revision_id', $revision->id)
            ->with('open_document_target_status', $status)
            ->with('status_project_document_project', $projectLabel);
    }
}
