<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\ExportJob;
use App\Models\SlaSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportCenterController extends Controller
{
    public function index(Request $request)
    {
        $jobs = ExportJob::where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('exports.index', compact('jobs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['type' => ['required', 'in:projects,costing,sla'], 'frequency' => ['nullable', 'in:daily,weekly,monthly'], 'scheduled_for' => ['nullable', 'date']]);
        $future = filled($data['scheduled_for'] ?? null) && now()->lt($data['scheduled_for']);
        $job = ExportJob::create(['user_id' => $request->user()->id, 'type' => $data['type'], 'filters' => [], 'status' => $future ? 'scheduled' : 'processing', 'frequency' => $data['frequency'] ?? null, 'scheduled_for' => $data['scheduled_for'] ?? null]);
        if (! $future) {
            $this->generate($job);
        }

        return back()->with('success', $future ? 'Export berhasil dijadwalkan.' : 'Export berhasil dibuat.');
    }

    public function download(Request $request, ExportJob $job)
    {
        abort_unless($job->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
        abort_unless($job->path && Storage::disk('local')->exists($job->path), 404);

        return Storage::disk('local')->download($job->path, $job->filename);
    }

    public function destroy(Request $request, ExportJob $job)
    {
        abort_unless($job->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
        if ($job->path) {
            Storage::disk('local')->delete($job->path);
        }$job->delete();

        return back()->with('success', 'Riwayat export dihapus.');
    }

    public function generate(ExportJob $job): void
    {
        [$headers, $rows] = $this->dataset($job->type);
        $filename = $job->type.'-'.$job->id.'-'.now()->format('Ymd-His').'.csv';
        $path = 'exports/'.$filename;
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }rewind($stream);
        if ($job->path && Storage::disk('local')->exists($job->path)) {
            Storage::disk('local')->delete($job->path);
        }
        Storage::disk('local')->put($path, stream_get_contents($stream));
        fclose($stream);
        $next = match ($job->frequency) {
            'daily' => now()->addDay(),'weekly' => now()->addWeek(),'monthly' => now()->addMonth(),default => null
        };
        $job->update(['filename' => $filename, 'path' => $path, 'status' => 'ready', 'row_count' => count($rows), 'last_run_at' => now(), 'scheduled_for' => $next]);
    }

    private function dataset(string $type): array
    {
        $revisions = DocumentRevision::latestPerProject()->with('project')->latest()->get();

        if ($type === 'sla') {
            $revisionLabels = DocumentRevision::whereIn('id', SlaSnapshot::query()->pluck('document_revision_id'))
                ->get()
                ->mapWithKeys(fn (DocumentRevision $revision) => [$revision->id => $revision->project?->part_number.' '.$revision->version_label]);
            $rows = SlaSnapshot::latest('snapshot_date')->get()->map(fn (SlaSnapshot $snapshot) => [
                $snapshot->snapshot_date?->format('Y-m-d'),
                $revisionLabels->get($snapshot->document_revision_id, '#'.$snapshot->document_revision_id),
                $snapshot->stage,
                $snapshot->pic,
                $snapshot->due_at?->format('Y-m-d H:i'),
                $snapshot->is_overdue ? 'Yes' : 'No',
                $snapshot->aging_days,
                $snapshot->compliance,
            ])->all();

            return [['Snapshot Date', 'Project Revision', 'Stage', 'PIC', 'Due At', 'Overdue', 'Aging Days', 'Compliance'], $rows];
        }

        $costing = CostingData::whereIn('tracking_revision_id', $revisions->pluck('id'))->with('customer')->latest('id')->get()->unique('tracking_revision_id')->keyBy('tracking_revision_id');

        if ($type === 'costing') {
            $rows = $costing->values()->map(fn (CostingData $item) => [
                $item->period,
                $item->assy_no,
                $item->assy_name,
                $item->customer?->name,
                $item->model,
                $item->material_cost,
                $item->labor_cost,
                $item->overhead_cost,
                $item->scrap_cost,
                $item->total_cost,
                $item->revenue,
                round($item->margin, 2),
                $item->updated_at?->format('Y-m-d H:i'),
            ])->all();

            return [['Period', 'Part Number', 'Project', 'Customer', 'Model', 'Material Cost', 'Labor Cost', 'Overhead Cost', 'Scrap Cost', 'Total Cost', 'Revenue', 'Margin %', 'Updated'], $rows];
        }

        $rows = $revisions->map(function ($r) use ($costing) {
            $c = $costing->get($r->id);

            return [$c?->assy_no ?: $r->project?->part_number, $c?->assy_name ?: $r->project?->part_name, $c?->customer?->name ?: $r->project?->customer, $c?->model ?: $r->project?->model, $r->version_label, $r->status_label, $r->pic_engineering, $r->pic_marketing, $r->updated_at?->format('Y-m-d H:i')];
        })->all();

        return [['Part Number', 'Project', 'Customer', 'Model', 'Revision', 'Status', 'PIC Engineering', 'PIC Marketing', 'Updated'], $rows];
    }
}
