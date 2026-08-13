@extends('layouts.app')

@section('title', 'COGM Resume Analysis')
@section('page-title', 'COGM Resume Analysis')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>COGM Resume Analysis</span>
@endsection

@section('content')
    @include('reports.resume-cogm.styles')
@php

    $chartPeriods = collect($costings ?? [])
        ->pluck('period')
        ->filter(fn ($period) => $period && $period !== '-')
        ->unique()
        ->sort()
        ->values();

    $lineGroups = collect($costings ?? [])
        ->pluck('line')
        ->filter(fn ($line) => $line && $line !== '-')
        ->unique()
        ->sort()
        ->values();

    if ($lineGroups->isEmpty()) {
        $lineGroups = collect(['COGM']);
    }

    $periodCogmTrend = $chartPeriods
        ->map(function ($period) use ($costings, $lineGroups) {
            $periodItems = collect($costings ?? [])->where('period', $period);

            return (object) [
                'period' => $period,
                'lines' => $lineGroups->mapWithKeys(function ($line) use ($periodItems) {
                    return [$line => $periodItems->where('line', $line)->sum('cogm')];
                }),
                'total' => $periodItems->sum('cogm'),
            ];
        })
        ->values();

    $compositionTrend = $chartPeriods
        ->map(function ($period) use ($costings) {
            $periodItems = collect($costings ?? [])->where('period', $period);
            $total = (float) $periodItems->sum('cogm');

            return (object) [
                'period' => $period,
                'material' => $total > 0 ? ($periodItems->sum('material') / $total * 100) : 0,
                'labor' => $total > 0 ? ($periodItems->sum('labor') / $total * 100) : 0,
                'overhead' => $total > 0 ? (($periodItems->sum('overhead') + $periodItems->sum('scrap')) / $total * 100) : 0,
            ];
        })
        ->values();

    $maxCogmChart = max(1, (float) $periodCogmTrend->max('total'));
    $lineColors = ['#2563eb', '#059669', '#f97316', '#7c3aed', '#ef4444', '#0f766e'];
    $componentColors = ['material' => '#2563eb', 'labor' => '#059669', 'overhead' => '#f97316'];

    $makeResumePoints = function ($items, $valueGetter, $maxValue) {
        $items = collect($items)->values();
        $total = $items->count();

        return $items->map(function ($item, $index) use ($valueGetter, $maxValue, $total) {
            $value = (float) $valueGetter($item);
            $x = $total <= 1 ? 50 : 6 + (($index / ($total - 1)) * 88);
            $y = 100 - ($maxValue > 0 ? (($value / $maxValue) * 78 + 12) : 12);
            $y = min(92, max(10, $y));

            return (object) [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $value,
            ];
        });
    };

    $resumePolyline = fn ($points) => collect($points)->map(fn ($p) => $p->x . ',' . $p->y)->implode(' ');

    $resumeCogmTicks = collect(range(3, 0))->map(function ($step) use ($maxCogmChart) {
        return 'Rp ' . number_format(($maxCogmChart / 3) * $step, 0, ',', '.');
    });

    $resumeCompositionTicks = collect(['100%', '67%', '33%', '0%']);

    $topLine = collect($costings ?? [])
        ->groupBy('line')
        ->map(fn ($items, $line) => (object) ['line' => $line ?: '-', 'total' => $items->sum('cogm')])
        ->sortByDesc('total')
        ->first();

    $latestComposition = $compositionTrend->last();

    $largestChange = $periodCogmTrend
        ->sliding(2)
        ->map(function ($pair) {
            if ($pair->count() < 2) {
                return null;
            }

            $previous = $pair->first();
            $current = $pair->last();

            return (object) [
                'from' => $previous->period,
                'to' => $current->period,
                'change' => abs($current->total - $previous->total),
            ];
        })
        ->filter()
        ->sortByDesc('change')
        ->first();

    $resumeInsights = collect([
        'Tren COGM menunjukkan total Rp ' . number_format($totalCogm ?? 0, 0, ',', '.') . ' dari ' . number_format($totalProjects ?? collect($costings ?? [])->count(), 0, ',', '.') . ' project.',
        $topLine ? 'Business model ' . $topLine->line . ' memiliki kontribusi COGM tertinggi.' : 'Belum ada business model dominan.',
        $latestComposition ? 'Material menjadi komponen terbesar sebesar ' . number_format($latestComposition->material, 1, ',', '.') . '% pada periode terakhir.' : 'Belum ada komposisi COGM.',
        $largestChange ? 'Perubahan terbesar terjadi pada ' . $largestChange->from . ' ke ' . $largestChange->to . '.' : 'Belum cukup data periode untuk menghitung perubahan terbesar.',
    ]);

    $renderPager = function ($paginator) {
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        if ($last <= 1) {
            return '';
        }

        $html = '<div class="pager">';

        if ($current > 1) {
            $html .= '<a href="' . e($paginator->previousPageUrl()) . '" aria-label="Previous">&lsaquo;</a>';
        } else {
            $html .= '<span class="disabled">&lsaquo;</span>';
        }

        $start = max(1, $current - 1);
        $end = min($last, $current + 1);

        if ($start > 1) {
            $html .= '<a href="' . e($paginator->url(1)) . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="disabled">...</span>';
            }
        }

        for ($page = $start; $page <= $end; $page++) {
            if ($page === $current) {
                $html .= '<span class="active">' . $page . '</span>';
            } else {
                $html .= '<a href="' . e($paginator->url($page)) . '">' . $page . '</a>';
            }
        }

        if ($end < $last) {
            if ($end < $last - 1) {
                $html .= '<span class="disabled">...</span>';
            }
            $html .= '<a href="' . e($paginator->url($last)) . '">' . $last . '</a>';
        }

        if ($current < $last) {
            $html .= '<a href="' . e($paginator->nextPageUrl()) . '" aria-label="Next">&rsaquo;</a>';
        } else {
            $html .= '<span class="disabled">&rsaquo;</span>';
        }

        $html .= '</div>';

        return $html;
    };
@endphp

    <div class="resume-cogm-page">
        @include('reports.resume-cogm.analytics')

        <div class="resume-two-column">
            @include('reports.resume-cogm.customer-summary')
            @include('reports.resume-cogm.project-details')
        </div>
    </div>
@endsection
