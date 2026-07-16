<?php

namespace App\Services\Project;

use App\Models\CostingData;
use App\Models\DocumentRevision;

class ProjectCompletenessService
{
    public function build(DocumentRevision $revision, ?CostingData $costing): array
    {
        $project = $revision->project;
        $openUnpriced = $revision->relationLoaded('unpricedParts')
            ? $revision->unpricedParts->whereNull('resolved_at')->count()
            : $revision->unpricedParts()->whereNull('resolved_at')->count();
        $materialCount = (int) ($costing?->material_breakdowns_count ?? 0);

        $checks = [
            $this->check('identity', 'Identitas project', 10, filled($project?->customer) && filled($project?->model) && filled($project?->part_number) && filled($project?->part_name), 'Customer, model, part number, atau nama part belum lengkap.', route('project', ['search' => $project?->part_number], false)),
            $this->check('pic', 'PIC project', 10, filled($revision->pic_engineering) && filled($revision->pic_marketing), 'PIC Engineering dan PIC Marketing harus ditentukan.', route('database.project-documents', ['search' => $project?->part_number], false)),
            $this->check('partlist', 'Dokumen Partlist', 15, filled($revision->partlist_file_path), 'File Partlist belum tersedia.', route('database.project-documents', ['search' => $project?->part_number], false)),
            $this->check('umh', 'Dokumen UMH', 10, filled($revision->umh_file_path), 'File UMH belum tersedia.', route('database.project-documents', ['search' => $project?->part_number], false)),
            $this->check('costing', 'Form Costing', 15, $costing !== null, 'Form Costing belum dibuat.', route('form', ['tracking_revision_id' => $revision->id], false)),
            $this->check('materials', 'Data material', 15, $materialCount > 0, 'Belum ada material yang tersimpan.', route('form', ['tracking_revision_id' => $revision->id], false) . '#materialFormSection'),
            $this->check('pricing', 'Harga material', 15, $costing !== null && $materialCount > 0 && $openUnpriced === 0, $openUnpriced . ' part masih belum memiliki harga final.', route('unpriced-parts', absolute: false)),
            $this->check('cycle_time', 'Cycle Time', 10, $this->hasCycleTime($costing?->cycle_times), 'Cycle Time belum diisi.', route('form', ['tracking_revision_id' => $revision->id], false) . '#cycleTimeFormSection'),
        ];

        $score = collect($checks)->where('complete', true)->sum('weight');

        return [
            'score' => $score,
            'level' => $score === 100 ? 'complete' : ($score >= 70 ? 'good' : ($score >= 40 ? 'warning' : 'danger')),
            'checks' => $checks,
            'missing' => collect($checks)->where('complete', false)->values()->all(),
            'complete_count' => collect($checks)->where('complete', true)->count(),
            'total_count' => count($checks),
        ];
    }

    private function check(string $key, string $label, int $weight, bool $complete, string $description, string $url): array
    {
        return compact('key', 'label', 'weight', 'complete', 'description', 'url');
    }

    private function hasCycleTime($rows): bool
    {
        if (is_string($rows)) $rows = json_decode($rows, true);
        return collect(is_array($rows) ? $rows : [])->contains(fn ($row) => is_array($row) && collect($row)->contains(fn ($value) => !in_array(trim((string) $value), ['', '0', '0.0', '0,0'], true)));
    }
}
