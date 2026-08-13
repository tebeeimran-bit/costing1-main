    <div class="resume-analytics-grid">
        <div class="resume-chart-panel">
            <div class="resume-chart-header">
                <h3 class="resume-chart-title">
                    Tren COGM per Periode
                    <span class="resume-info-dot">i</span>
                </h3>
                <span class="resume-period-pill">Periode: Monthly</span>
            </div>

            <div class="resume-chart-unit">Rp</div>

            <div class="resume-line-chart">
                <div class="resume-y-labels">
                    @foreach($resumeCogmTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>

                <div class="resume-chart-area">
                    <svg class="resume-chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        @foreach($lineGroups as $lineIndex => $line)
                            @php
                                $linePoints = $makeResumePoints($periodCogmTrend, fn ($row) => $row->lines[$line] ?? 0, $maxCogmChart);
                                $lineColor = $lineColors[$lineIndex % count($lineColors)];
                                $lastPoint = $linePoints->last();
                            @endphp

                            <polyline points="{{ $resumePolyline($linePoints) }}" fill="none" stroke="{{ $lineColor }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            @foreach($linePoints as $point)
                                <circle cx="{{ $point->x }}" cy="{{ $point->y }}" r="1.5" fill="{{ $lineColor }}" vector-effect="non-scaling-stroke" />
                            @endforeach

                            @if($lastPoint && $lastPoint->value > 0)
                                <text class="resume-end-label" x="{{ min(94, $lastPoint->x + 2.8) }}" y="{{ $lastPoint->y }}" fill="{{ $lineColor }}">
                                    Rp {{ number_format($lastPoint->value, 0, ',', '.') }}
                                </text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="resume-chart-x" style="--count:{{ max(1, $chartPeriods->count()) }};">
                @forelse($chartPeriods as $period)
                    <span>{{ $period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>

            <div class="resume-chart-legend">
                @foreach($lineGroups as $lineIndex => $line)
                    <span class="resume-legend-item">
                        <span class="resume-legend-dot" style="background: {{ $lineColors[$lineIndex % count($lineColors)] }};"></span>
                        {{ $line }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="resume-chart-panel">
            <div class="resume-chart-header">
                <h3 class="resume-chart-title">
                    Komposisi COGM
                    <span class="resume-info-dot">i</span>
                </h3>
                <span class="resume-period-pill">Periode: Monthly</span>
            </div>

            <div class="resume-chart-unit">%</div>

            <div class="resume-line-chart">
                <div class="resume-y-labels">
                    @foreach($resumeCompositionTicks as $tick)
                        <span>{{ $tick }}</span>
                    @endforeach
                </div>

                <div class="resume-chart-area">
                    <svg class="resume-chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        @foreach(['material' => 'Material', 'labor' => 'Labor', 'overhead' => 'Overhead'] as $key => $label)
                            @php
                                $componentPoints = $makeResumePoints($compositionTrend, fn ($row) => $row->{$key}, 100);
                                $componentColor = $componentColors[$key];
                                $lastComponentPoint = $componentPoints->last();
                            @endphp

                            <polyline points="{{ $resumePolyline($componentPoints) }}" fill="none" stroke="{{ $componentColor }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            @foreach($componentPoints as $point)
                                <circle cx="{{ $point->x }}" cy="{{ $point->y }}" r="1.5" fill="{{ $componentColor }}" vector-effect="non-scaling-stroke" />
                            @endforeach

                            @if($lastComponentPoint)
                                <text class="resume-end-label" x="{{ min(94, $lastComponentPoint->x + 2.8) }}" y="{{ $lastComponentPoint->y }}" fill="{{ $componentColor }}">
                                    {{ number_format($lastComponentPoint->value, 1, ',', '.') }}%
                                </text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="resume-chart-x" style="--count:{{ max(1, $chartPeriods->count()) }};">
                @forelse($chartPeriods as $period)
                    <span>{{ $period }}</span>
                @empty
                    <span>Belum ada data</span>
                @endforelse
            </div>

            <div class="resume-chart-legend">
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#2563eb;"></span>Material</span>
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#059669;"></span>Labor</span>
                <span class="resume-legend-item"><span class="resume-legend-dot" style="background:#f97316;"></span>Overhead</span>
            </div>
        </div>

        <div class="resume-insight-card">
            <div class="resume-insight-header">
                <div class="resume-insight-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M9 18h6" />
                        <path d="M10 22h4" />
                        <path d="M12 2a7 7 0 0 0-4 12.74c.63.44 1 1.17 1 1.94V17h6v-.32c0-.77.37-1.5 1-1.94A7 7 0 0 0 12 2Z" />
                    </svg>
                </div>
                <h3 class="resume-insight-title">Insight Utama</h3>
            </div>

            <div class="resume-insight-list">
                @foreach($resumeInsights as $insight)
                    <div class="resume-insight-item">
                        <span class="resume-insight-bullet"></span>
                        <span>{{ $insight }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
