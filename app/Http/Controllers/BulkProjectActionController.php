<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\ProjectTaskSetting;
use App\Services\Project\ProjectActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BulkProjectActionController extends Controller
{
    public function __invoke(Request $request, ProjectActivityService $activities)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'admin_costing', 'coordinator_costing', 'editor'], true), 403);
        $validated = $request->validate([
            'revision_ids' => ['required', 'array', 'min:1', 'max:100'],
            'revision_ids.*' => ['integer', 'distinct', 'exists:document_revisions,id'],
            'bulk_action' => ['required', 'in:deadline,pic_engineering,pic_marketing,export'],
            'bulk_value' => ['nullable', 'string', 'max:255'],
        ]);
        $revisions = DocumentRevision::with('project')->whereIn('id', $validated['revision_ids'])->get();

        if ($validated['bulk_action'] === 'export') return $this->export($revisions);
        if ($validated['bulk_action'] === 'deadline') {
            $request->validate(['bulk_value' => ['required', 'date']]);
        } else {
            $request->validate(['bulk_value' => ['required', 'string', 'max:255']]);
        }
        $value = trim((string) ($validated['bulk_value'] ?? ''));
        if ($value === '') return back()->withErrors(['bulk_value' => 'Nilai bulk action wajib diisi.']);

        DB::transaction(function () use ($request, $revisions, $validated, $value, $activities) {
            foreach ($revisions as $revision) {
                if ($validated['bulk_action'] === 'deadline') {
                    $dueAt = Carbon::parse($value)->endOfDay();
                    ProjectTaskSetting::updateOrCreate(['document_revision_id' => $revision->id], ['due_at' => $dueAt, 'set_by_id' => $request->user()->id]);
                    $activities->record($revision->id, 'bulk_deadline_updated', 'Deadline updated in bulk', 'Due date set to ' . $dueAt->format('d M Y') . '.');
                } else {
                    $field = $validated['bulk_action'];
                    $revision->update([$field => $value]);
                    $activities->record($revision->id, 'bulk_pic_updated', 'PIC updated in bulk', str_replace('_', ' ', ucfirst($field)) . ' changed to ' . $value . '.');
                }
            }
        });

        return back()->with('success', $revisions->count() . ' project berhasil diperbarui.');
    }

    private function export($revisions)
    {
        $costing = CostingData::with('customer')->whereIn('tracking_revision_id', $revisions->pluck('id'))->get()->keyBy('tracking_revision_id');
        return response()->streamDownload(function () use ($revisions, $costing) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Part Number', 'Part Name', 'Customer', 'Model', 'Revision', 'PIC Engineering', 'PIC Marketing', 'Status', 'Last Updated']);
            foreach ($revisions as $revision) {
                $data = $costing->get($revision->id); $project = $revision->project;
                fputcsv($out, [$data?->assy_no ?: $project?->part_number, $data?->assy_name ?: $project?->part_name, $data?->customer?->name ?: $project?->customer, $data?->model ?: $project?->model, $revision->version_label, $revision->pic_engineering, $revision->pic_marketing, $revision->status_label, $revision->updated_at?->format('Y-m-d H:i')]);
            }
            fclose($out);
        }, 'selected-projects-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
