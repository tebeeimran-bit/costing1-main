        <!-- Section E: Cycle Time -->
        <div class="card form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 8v4l3 3" />
                    <circle cx="12" cy="12" r="10" />
                </svg>
                Cycle Time
                <div class="section-actions">
                    <button type="submit" class="btn btn-primary btn-sm section-update-btn" name="update_section" value="cycle_time" data-section="cycle_time" formnovalidate>
                        Update
                    </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="triggerUmhImport()">
                        Import UMH
                    </button>
<button type="button" class="btn btn-secondary" onclick="addCycleTimeRow()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Baris
                    </button>
                </div>
            </div>

            <div class="cycle-table-container">
                <table class="cycle-table" id="cycleTimeTable">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>PROCESS</th>
                            <th>QTY</th>
                            <th>TIME (HOUR)</th>
                            <th>TIME (SEC)</th>
                            <th>TIME (SEC) / 1 Qty</th>
                            <th>Cost / SEC</th>
                            <th>Cost / Unit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cycleTimeTableBody">
                        @php
                            $cycleTimes = old('cycle_times', $costingData->cycle_times ?? []);
                            if (!is_array($cycleTimes)) {
                                $cycleTimes = [];
                            }

                            if (count($cycleTimes) === 0 && isset($cycleTimeTemplates) && $cycleTimeTemplates->count() > 0) {
                                $cycleTimes = $cycleTimeTemplates->map(function ($template) {
                                    return [
                                        'process' => $template->process,
                                    ];
                                })->toArray();
                            }

                            $cycleTemplateProcesses = ($cycleTimeTemplates ?? collect())->pluck('process')->filter()->values();

                            $initialCycleCount = count($cycleTimes) > 0 ? count($cycleTimes) : 5;
                        @endphp
                        @if(count($cycleTimes) > 0)
                            @foreach($cycleTimes as $index => $cycle)
                                <tr data-cycle-row="{{ $index }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <select class="form-select ct-process" name="cycle_times[{{ $index }}][process]">
                                            <option value="">-- Pilih Process --</option>
                                            @foreach(($cycleTimeTemplates ?? collect()) as $template)
                                                <option value="{{ $template->process }}" {{ (($cycle['process'] ?? '') === $template->process) ? 'selected' : '' }}>
                                                    {{ $template->process }}
                                                </option>
                                            @endforeach
                                            @if(!empty($cycle['process'] ?? '') && !$cycleTemplateProcesses->contains($cycle['process']))
                                                <option value="{{ $cycle['process'] }}" selected>{{ $cycle['process'] }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-qty"
                                            name="cycle_times[{{ $index }}][qty]"
                                            value="{{ $cycle['qty'] ?? '' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-hour"
                                            name="cycle_times[{{ $index }}][time_hour]"
                                            value="{{ $cycle['time_hour'] ?? '' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-sec"
                                            name="cycle_times[{{ $index }}][time_sec]"
                                            value="{{ isset($cycle['time_sec']) && $cycle['time_sec'] !== '' ? round((float) $cycle['time_sec']) : '' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-sec-per"
                                            name="cycle_times[{{ $index }}][time_sec_per_qty]"
                                            value="{{ isset($cycle['time_sec_per_qty']) && $cycle['time_sec_per_qty'] !== '' ? round((float) $cycle['time_sec_per_qty']) : '' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-cost-sec"
                                            name="cycle_times[{{ $index }}][cost_per_sec]"
                                            value="{{ $cycle['cost_per_sec'] ?? '10.33' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-cost-unit"
                                            name="cycle_times[{{ $index }}][cost_per_unit]"
                                            value="{{ isset($cycle['cost_per_unit']) && $cycle['cost_per_unit'] !== '' ? number_format((float) $cycle['cost_per_unit'], 2, ',', '.') : '' }}" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeCycleTimeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @for($i = 0; $i < 5; $i++)
                                <tr data-cycle-row="{{ $i }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <select class="form-select ct-process" name="cycle_times[{{ $i }}][process]">
                                            <option value="">-- Pilih Process --</option>
                                            @foreach(($cycleTimeTemplates ?? collect()) as $template)
                                                <option value="{{ $template->process }}">{{ $template->process }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-qty"
                                            name="cycle_times[{{ $i }}][qty]" value="" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-hour"
                                            name="cycle_times[{{ $i }}][time_hour]" value="" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-sec"
                                            name="cycle_times[{{ $i }}][time_sec]" value="" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-sec-per"
                                            name="cycle_times[{{ $i }}][time_sec_per_qty]" value="" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-cost-sec"
                                            name="cycle_times[{{ $i }}][cost_per_sec]" value="10.33" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-input ct-cost-unit"
                                            name="cycle_times[{{ $i }}][cost_per_unit]" value="" onchange="calculateCycleRow(this)">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-secondary" onclick="removeCycleTimeRow(this)"
                                            style="padding: 0.5rem;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background: #1f2937;">
                            <td colspan="3" style="text-align: right; font-weight: 700; color: #ffffff;">Total</td>
                            <td class="calculated" id="cycleTotalHour" style="font-weight: 800; color: #ffffff; text-align: right;">0</td>
                            <td class="calculated" id="cycleTotalSec" style="font-weight: 800; color: #ffffff; text-align: right;">0</td>
                            <td></td>
                            <td></td>
                            <td class="calculated" id="cycleTotalCostUnit" style="font-weight: 800; color: #ffffff; text-align: right;">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
