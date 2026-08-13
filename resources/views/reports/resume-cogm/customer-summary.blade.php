        <div class="resume-panel">
            <div class="resume-panel-header">
                <h3 class="resume-panel-title">Ringkasan per Customer</h3>
            </div>

            <div class="resume-table-wrap">
                <table class="resume-table customer-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th class="text-center">Projects</th>
                            <th class="text-right">Total COGM</th>
                            <th class="text-right">Total Potensial</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerSummary as $index => $c)
                            <tr>
                                <td>{{ $customerSummary->firstItem() + $index }}</td>
                                <td><strong>{{ $c->customer }}</strong></td>
                                <td class="text-center">{{ number_format($c->count, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->total_cogm, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($c->total_potential, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada data customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="table-footer">
                    <span>
                        Menampilkan {{ $customerSummary->firstItem() ?? 0 }}-{{ $customerSummary->lastItem() ?? 0 }}
                        dari {{ number_format($customerSummary->total(), 0, ',', '.') }} customer
                    </span>
                    {!! $renderPager($customerSummary) !!}
                </div>
            </div>
        </div>
