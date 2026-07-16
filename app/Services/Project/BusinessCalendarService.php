<?php

namespace App\Services\Project;

use App\Models\CompanyHoliday;
use Carbon\Carbon;

class BusinessCalendarService
{
    public function addBusinessDays(Carbon $start, int $days): Carbon
    {
        $date = $start->copy();
        $holidays = CompanyHoliday::query()->where('is_active', true)->pluck('holiday_date')->map(fn ($value) => Carbon::parse($value)->toDateString())->all();
        $added = 0;
        while ($added < max(0, $days)) {
            $date->addDay();
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidays, true)) {
                $added++;
            }
        }

        return $date;
    }
}
