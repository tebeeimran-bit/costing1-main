        <div class="resume-panel">
            <div class="resume-panel-header">
                <h3 class="resume-panel-title">Detail COGM per Project</h3>

                <span class="resume-panel-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    Klik project untuk buka Form Costing
                </span>
            </div>

            <div class="resume-table-wrap">
                <table class="resume-table project-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Model</th>
                            <th>Assy No</th>
                            <th>Assy Name</th>
                            <th>Status</th>
                            <th class="text-right">Material</th>
                            <th class="text-right">Labor</th>
                            <th class="text-right">Overhead</th>
                            <th class="text-right">COGM</th>
                            <th class="text-right">Forecast</th>
                            <th class="text-right">Life (Y)</th>
                            <th class="text-right">Potensial Cost</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projectDetails as $index => $c)
                            <tr>
                                <td>{{ $projectDetails->firstItem() + $index }}</td>
                                <td>{{ $c->customer }}</td>
                                <td>{{ $c->model }}</td>
                                <td>
                                    <a href="{{ $c->form_url }}" class="project-link assy-no-link" title="Buka Form Costing {{ $c->assy_no }}">
                                        <span>{{ $c->assy_no }}</span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path d="M7 17L17 7" />
                                            <path d="M9 7h8v8" />
                                            <path d="M19 13v6H5V5h6" />
                                        </svg>
                                    </a>
                                </td>
                                <td>{{ $c->assy_name }}</td>
                                <td>
                                    <span class="status-pill">
                                        <span class="status-dot {{ strtolower($c->status) }}"></span>
                                        {{ $c->status }}
                                    </span>
                                </td>
                                <td class="text-right">Rp {{ number_format($c->material, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->labor, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->overhead, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: 900;">Rp {{ number_format($c->cogm, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($c->forecast, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($c->project_period, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: 900;">Rp {{ number_format($c->potential, 0, ',', '.') }}</td>
                                <td>
                                    <div class="note-stack">
                                        @if($c->last_updated_at)
                                            <span class="submission-update-note">COGM diperbarui {{ $c->update_count }}x<small>{{ $c->last_updated_by ?: 'User' }} · {{ $c->last_updated_at->format('d/m/Y H:i') }}</small></span>
                                        @endif
                                        @if($c->is_full_price)
                                            <span class="note-badge full">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                    <path d="M20 6L9 17l-5-5" />
                                                </svg>
                                                Full Price
                                            </span>
                                        @else
                                            @php
                                            $priceNotes = collect();

                                            if (($c->missing_part_count ?? 0) > 0) {
                                                $priceNotes->push(number_format($c->missing_part_count, 0, ',', '.') . ' part belum ada harga');
                                            }

                                            if (($c->estimate_part_count ?? 0) > 0) {
                                                $priceNotes->push(number_format($c->estimate_part_count, 0, ',', '.') . ' part masih estimate');
                                            }

                                            if ($c->cycle_time_incomplete ?? false) {
                                                $priceNotes->push('Cycle time belum lengkap');
                                            }

                                            if ($c->tooling_depreciation_incomplete ?? false) {
                                                $priceNotes->push('Depresiasi tooling cost belum lengkap');
                                            }
                                        @endphp

                                        @if($priceNotes->isEmpty())
                                            <span class="price-status-badge full">Full Price</span>
                                        @else
                                            <div class="price-status-notes">
                                                @foreach($priceNotes as $note)
                                                    <div>- {{ $note }}</div>
                                                @endforeach
                                            </div>
                                        @endif

                                            @if($c->estimate_part_count > 0)
                                                <span class="note-badge estimate">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                                        <path d="M12 9v4" />
                                                        <path d="M12 17h.01" />
                                                    </svg>
                                                    {{ $c->estimate_part_count }} part masih estimate
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="empty-state">Belum ada data COGM per project.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="table-footer">
                    <span>
                        Menampilkan {{ $projectDetails->firstItem() ?? 0 }}-{{ $projectDetails->lastItem() ?? 0 }}
                        dari {{ number_format($projectDetails->total(), 0, ',', '.') }} project
                    </span>
                    {!! $renderPager($projectDetails) !!}
                </div>
            </div>
        </div>
