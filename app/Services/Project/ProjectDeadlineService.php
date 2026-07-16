<?php

namespace App\Services\Project;

use App\Models\DocumentRevision;
use Carbon\CarbonInterface;

class ProjectDeadlineService
{
    private const SLA_DAYS = ['documents' => 3, 'pricing' => 2, 'costing' => 3, 'approval' => 1, 'marketing' => 2];

    public function resolve(DocumentRevision $revision, array $workflow): array
    {
        $currentStep = collect($workflow['steps'])->first(fn ($step) => !$step['complete']);
        $category = $currentStep['key'] ?? 'marketing';
        $slaDays = self::SLA_DAYS[$category] ?? 3;
        $custom = $revision->taskSetting?->due_at;
        $dueAt = ($custom ?: $revision->updated_at?->copy()->addDays($slaDays) ?: now()->addDays($slaDays))->endOfDay();
        $seconds = now()->diffInSeconds($dueAt, false);
        $daysRemaining = $seconds >= 0 ? (int) ceil($seconds / 86400) : -(int) ceil(abs($seconds) / 86400);

        return [
            'category' => $category,
            'sla_days' => $slaDays,
            'due_at' => $dueAt,
            'is_custom' => $custom !== null,
            'is_overdue' => $dueAt->isPast() && !$workflow['is_complete'],
            'days_remaining' => $daysRemaining,
            'aging_days' => (int) ($revision->updated_at?->diffInDays(now()) ?? 0),
            'label' => $this->label($daysRemaining, $workflow['is_complete']),
        ];
    }

    private function label(int $days, bool $complete): string
    {
        if ($complete) return 'Completed';
        if ($days < 0) return abs($days) . ' day(s) overdue';
        if ($days === 0) return 'Due today';
        return $days . ' day(s) remaining';
    }
}
