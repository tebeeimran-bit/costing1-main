<?php

namespace App\Services\Project;

use App\Models\DocumentRevision;
use App\Models\ProjectTaskSetting;

class ProjectDeadlineService
{
    private const SLA_DAYS = ['documents' => 3, 'pricing' => 2, 'costing' => 3, 'approval' => 1, 'marketing' => 2];

    public function __construct(private readonly BusinessCalendarService $calendar) {}

    public function resolve(DocumentRevision $revision, array $workflow): array
    {
        $currentStep = collect($workflow['steps'])->first(fn ($step) => ! $step['complete']);
        $category = $currentStep['key'] ?? 'marketing';
        $slaDays = self::SLA_DAYS[$category] ?? 3;
        $setting = $this->syncStage($revision, $category);
        $custom = $setting->due_at;
        $stageEnteredAt = $setting->stage_entered_at ?: $revision->updated_at ?: now();
        $dueAt = ($custom ?: $this->calendar->addBusinessDays($stageEnteredAt, $slaDays))->endOfDay();
        $seconds = now()->diffInSeconds($dueAt, false);
        $daysRemaining = $seconds >= 0 ? (int) ceil($seconds / 86400) : -(int) ceil(abs($seconds) / 86400);

        return [
            'category' => $category,
            'sla_days' => $slaDays,
            'due_at' => $dueAt,
            'is_custom' => $custom !== null,
            'is_overdue' => $dueAt->isPast() && ! $workflow['is_complete'],
            'days_remaining' => $daysRemaining,
            'aging_days' => (int) $stageEnteredAt->diffInDays(now()),
            'label' => $this->label($daysRemaining, $workflow['is_complete']),
        ];
    }

    private function syncStage(DocumentRevision $revision, string $category): ProjectTaskSetting
    {
        $setting = $revision->taskSetting;

        if (! $setting) {
            return ProjectTaskSetting::create([
                'document_revision_id' => $revision->id,
                'workflow_stage' => $category,
                'stage_entered_at' => $revision->updated_at ?: now(),
            ]);
        }

        if ($setting->workflow_stage !== $category) {
            $setting->update([
                'workflow_stage' => $category,
                'stage_entered_at' => now(),
            ]);
        } elseif (! $setting->stage_entered_at) {
            $setting->update(['stage_entered_at' => $revision->updated_at ?: now()]);
        }

        return $setting->refresh();
    }

    private function label(int $days, bool $complete): string
    {
        if ($complete) {
            return 'Completed';
        }
        if ($days < 0) {
            return abs($days).' day(s) overdue';
        }
        if ($days === 0) {
            return 'Due today';
        }

        return $days.' day(s) remaining';
    }
}
