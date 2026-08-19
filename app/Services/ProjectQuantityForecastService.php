<?php

namespace App\Services;

use App\Models\DocumentRevision;
use App\Models\ProjectQuantityForecast;
use Illuminate\Support\Collection;

class ProjectQuantityForecastService
{
    public function sync(DocumentRevision $revision, bool $enabled, string $periodType, array $rows, string $uom): Collection
    {
        $revision->quantityForecasts()->delete();

        if (! $enabled) {
            return collect();
        }

        $periodType = $periodType === 'month' ? 'month' : 'year';
        $normalized = collect($rows)->map(function (array $row) use ($revision, $periodType, $uom) {
            return [
                'document_revision_id' => $revision->id,
                'period_type' => $periodType,
                'year_number' => (int) ($row['year_number'] ?? 0),
                'calendar_year' => isset($row['calendar_year']) ? (int) $row['calendar_year'] : null,
                'month_number' => $periodType === 'month' ? (int) ($row['month_number'] ?? 0) : null,
                'quantity' => (float) ($row['quantity'] ?? 0),
                'uom' => $uom,
            ];
        })->filter(fn (array $row) => $row['year_number'] > 0
            && ($periodType === 'year' || ($row['month_number'] >= 1 && $row['month_number'] <= 12)));

        foreach ($normalized as $row) {
            ProjectQuantityForecast::create($row);
        }

        return $revision->quantityForecasts()->get();
    }

    public function summary(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return ['total' => 0, 'years' => 0, 'average' => 0, 'basis' => 'per_year'];
        }

        $periodType = (string) $rows->first()->period_type;
        $years = (int) $rows->max('year_number');
        $total = (float) $rows->sum(fn ($row) => (float) $row->quantity);
        $divisor = $periodType === 'month' ? $rows->count() : $years;

        return [
            'total' => $total,
            'years' => $years,
            'average' => $divisor > 0 ? $total / $divisor : 0,
            'basis' => $periodType === 'month' ? 'per_month' : 'per_year',
        ];
    }
}
