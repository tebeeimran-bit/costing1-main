<?php

namespace App\Services\Notification;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\NotificationPreference;
use App\Models\NotificationState;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectNotificationService
{
    public function forUser(User $user): Collection
    {
        $enabled = NotificationPreference::query()->where('user_id', $user->id)->value('enabled_types') ?? NotificationPreference::TYPES;
        if (is_string($enabled)) $enabled = json_decode($enabled, true) ?: NotificationPreference::TYPES;

        $revisions = DocumentRevision::query()
            ->with('project')
            ->whereIn('id', DocumentRevision::query()->selectRaw('MAX(id)')->whereNotNull('document_project_id')->groupBy('document_project_id'))
            ->get();
        $costingByRevision = CostingData::query()
            ->with(['materialBreakdowns:id,costing_data_id,part_no,amount1,cn_type'])
            ->whereIn('tracking_revision_id', $revisions->pluck('id'))
            ->latest('id')->get()->unique('tracking_revision_id')->keyBy('tracking_revision_id');

        $items = collect();
        foreach ($revisions as $revision) {
            $project = $revision->project;
            if (!$project) continue;
            $identity = trim((string) $project->customer) . ' - ' . trim((string) $project->model);
            $costing = $costingByRevision->get($revision->id);
            $costingUrl = route('form', array_filter(['id' => $costing?->id, 'tracking_revision_id' => $revision->id]), false);
            $version = $revision->updated_at?->timestamp ?? 0;

            if (($revision->a00 ?? null) !== 'ada' && ($revision->a04 ?? null) !== 'ada' && ($revision->a05 ?? null) !== 'ada') {
                $items->push($this->item('document:' . $revision->id . ':' . $version, 'document', 'Dokumen project belum ada', $identity . ' - A00 belum ada', 'Minimal salah satu dokumen A00, A04, atau A05 harus terisi.', 'Cek Dokumen', route('database.project-documents', ['search' => $project->part_number], false), 'orange', $revision->updated_at));
            }

            $materials = $costing?->materialBreakdowns ?? collect();
            if ($materials->isEmpty() || !$this->hasCycleTime($costing?->cycle_times)) {
                $items->push($this->item('project:' . $revision->id . ':' . $version, 'project', 'Project belum costing', $identity . ' - Belum costing', 'Material atau Cycle Time masih perlu dilengkapi.', 'Cek Project', $costingUrl, 'blue', $revision->updated_at));
                continue;
            }

            $missing = $this->uniqueParts($materials->filter(fn ($row) => (float) ($row->amount1 ?? 0) <= 0));
            $estimate = $this->uniqueParts($materials->filter(fn ($row) => strtoupper(trim((string) ($row->cn_type ?? ''))) === 'E'));
            if ($missing || $estimate) {
                $issues = collect([$missing ? $missing . ' part belum ada harga' : null, $estimate ? $estimate . ' part masih estimate' : null])->filter()->implode(', ');
                $items->push($this->item('pricing:' . $revision->id . ':' . $version, 'pricing', 'Project belum full priced', $identity . ' - ' . $issues, 'Harga material belum sepenuhnya final.', 'Cek Harga', $costingUrl, 'purple', $revision->updated_at));
            }
        }

        ProjectComment::query()->with(['user:id,name', 'revision.project'])->whereJsonContains('mentioned_user_ids', $user->id)->where('created_at', '>=', now()->subDays(14))->latest()->limit(10)->get()->each(function ($comment) use ($items) {
            $items->push($this->item('mention:' . $comment->id, 'mention', 'Anda disebut dalam komentar', ($comment->user?->name ?: 'Anggota tim') . ' menyebut Anda pada ' . ($comment->revision?->project?->part_number ?: 'project'), Str::limit($comment->body, 90), 'Buka Diskusi', route('project-collaboration.show', $comment->document_revision_id, false), 'purple', $comment->created_at));
        });

        $states = NotificationState::query()->where('user_id', $user->id)->whereIn('notification_key', $items->pluck('key'))->get()->keyBy('notification_key');

        return $items->filter(fn ($item) => in_array($item['type'], $enabled, true))
            ->map(function ($item) use ($states) {
                $state = $states->get($item['key']);
                $item['is_read'] = $state?->read_at !== null;
                $item['is_dismissed'] = $state?->dismissed_at !== null;
                return $item;
            })->reject(fn ($item) => $item['is_dismissed'])->sortByDesc('created_at')->values();
    }

    private function item(string $key, string $type, string $title, string $line, string $description, string $button, string $url, string $color, $createdAt): array
    {
        return compact('key', 'type', 'title', 'line', 'description', 'url', 'color') + ['button_label' => $button, 'created_at' => $createdAt];
    }

    private function uniqueParts(Collection $rows): int
    {
        return $rows->map(fn ($row, $index) => trim((string) $row->part_no) !== '' ? strtoupper(trim($row->part_no)) : 'ROW-' . $index)->unique()->count();
    }

    private function hasCycleTime($rows): bool
    {
        if (is_string($rows)) $rows = json_decode($rows, true);
        return collect(is_array($rows) ? $rows : [])->contains(fn ($row) => is_array($row) && collect($row)->contains(fn ($value) => !in_array(trim((string) $value), ['', '0', '0.0', '0,0'], true)));
    }
}
