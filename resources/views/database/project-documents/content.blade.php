    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fde68a;">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="doc-summary-cards">
        <div class="doc-summary-card" style="background: #2563eb;">
            <span class="doc-label">A00 (RFQ/RFI)</span>
            <span class="doc-count">{{ $a00Count }}</span>
        </div>
        <div class="doc-summary-card" style="background: #dc2626;">
            <span class="doc-label">A04 (Cancelled/Failed)</span>
            <span class="doc-count">{{ $a04Count }}</span>
        </div>
        <div class="doc-summary-card" style="background: #16a34a;">
            <span class="doc-label">A05 (Die Go)</span>
            <span class="doc-count">{{ $a05Count }}</span>
        </div>
    </div>

    @php
        $partlistMasukCount = $partlistMasukCount ?? 0;
        $belumPartlistCount = $belumPartlistCount ?? 0;
        $revisiPartlistCount = $revisiPartlistCount ?? 0;
        $umhMasukCount = $umhMasukCount ?? 0;
        $belumUmhCount = $belumUmhCount ?? 0;
        $revisiUmhCount = $revisiUmhCount ?? 0;
    @endphp

    <div class="engineering-doc-panel">
        <div class="engineering-doc-head">
            <h3 class="engineering-doc-title">Pengumpulan Dokumen Engineering</h3>
            <div class="engineering-doc-actions">
                <div class="engineering-doc-note">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 8h.01"></path>
                        <path d="M11 12h1v5h1"></path>
                    </svg>
                    Pengumpulan Partlist dapat lebih dari 1x karena adanya revisi atau perubahan spesifikasi dari customer.
                </div>
                <a href="{{ route('database.document-recap', absolute: false) }}" class="btn-folder-storage">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V7Z"></path>
                        <path d="M3 10h18v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7Z"></path>
                    </svg>
                    Buka Folder Penyimpanan
                </a>
            </div>
        </div>

        <div class="engineering-doc-cards">
            <div class="engineering-doc-card blue">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M8 8h8M8 12h8M8 16h5"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Partlist Masuk</div>
                    <div class="engineering-doc-count">{{ number_format($partlistMasukCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card yellow">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Belum Partlist</div>
                    <div class="engineering-doc-count">{{ number_format($belumPartlistCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card orange">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M21 12a9 9 0 0 1-15.4 6.36"></path>
                        <path d="M3 12A9 9 0 0 1 18.4 5.64"></path>
                        <path d="M3 3v6h6"></path>
                        <path d="M21 21v-6h-6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Revisi Partlist</div>
                    <div class="engineering-doc-count">{{ number_format($revisiPartlistCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">revisi</div>
                </div>
            </div>

            <div class="engineering-doc-card green">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M7 3h7l5 5v13H7z"></path>
                        <path d="M14 3v5h5"></path>
                        <path d="M10 13h6M10 17h6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">UMH Masuk</div>
                    <div class="engineering-doc-count">{{ number_format($umhMasukCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card red">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Belum UMH</div>
                    <div class="engineering-doc-count">{{ number_format($belumUmhCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">project</div>
                </div>
            </div>

            <div class="engineering-doc-card purple">
                <div class="engineering-doc-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <path d="M21 12a9 9 0 0 1-15.4 6.36"></path>
                        <path d="M3 12A9 9 0 0 1 18.4 5.64"></path>
                        <path d="M3 3v6h6"></path>
                        <path d="M21 21v-6h-6"></path>
                    </svg>
                </div>
                <div>
                    <div class="engineering-doc-label">Revisi UMH</div>
                    <div class="engineering-doc-count">{{ number_format($revisiUmhCount, 0, ',', '.') }}</div>
                    <div class="engineering-doc-unit">revisi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('database.project-documents') }}" id="docFilterForm">
    <div class="doc-filter-bar">
        <a href="{{ route('project', absolute: false) }}" class="btn btn-secondary" style="padding: 0.55rem 1rem; font-size: 0.85rem; white-space: nowrap;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"></path>
                <path d="M12 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Project
        </a>
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0.8rem; border: 1px solid var(--slate-200); border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); max-width: 420px; width: 100%;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--slate-400); flex-shrink: 0;">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="M20 20l-3.5-3.5"></path>
            </svg>
            <input
                type="text"
                name="search"
                id="docSearchInput"
                placeholder="Cari customer, model, part name..."
                value="{{ $search }}"
                style="border: 0; outline: none; width: 100%; padding: 0.7rem 0; font-size: 0.95rem; color: var(--slate-800); background: transparent;"
            >
        </div>
        <select name="status" id="docFilterStatus" class="form-select" onchange="document.getElementById('docFilterForm').submit()" style="padding: 0.55rem 0.75rem; font-size: 0.85rem;">
            <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua Dokumen</option>
            <option value="a00" {{ $statusFilter === 'a00' ? 'selected' : '' }}>A00 (RFQ/RFI)</option>
            <option value="a04" {{ $statusFilter === 'a04' ? 'selected' : '' }}>A04 (Cancelled/Failed)</option>
            <option value="a05" {{ $statusFilter === 'a05' ? 'selected' : '' }}>A05 (Die Go)</option>
        </select>
        <select name="per_page" class="form-select" onchange="document.getElementById('docFilterForm').submit()" style="padding: 0.55rem 0.75rem; font-size: 0.85rem; width: auto;">
            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / hal</option>
            <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / hal</option>
            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 / hal</option>
            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / hal</option>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem; font-size: 0.85rem; white-space:nowrap;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            Cari
        </button>
        @if($search || $statusFilter)
        <a href="{{ route('database.project-documents') }}" class="btn btn-secondary" style="padding: 0.55rem 1rem; font-size: 0.85rem;">Reset</a>
        @endif
    </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pengumpulan Dokumen A00, A04 &amp; A05</h3>
            <span style="font-size: 0.8rem; color: var(--slate-500);">Menampilkan {{ $pagedRows->firstItem() }}–{{ $pagedRows->lastItem() }} dari {{ $pagedRows->total() }} data</span>
        </div>
        <div class="material-table-container">
            <table class="data-table" id="docProjectTable" style="min-width: 1940px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No.</th>
                        <th>Customer</th>
                        <th>Model</th>
                        <th>Part Name</th>
                        <th>Part No</th>
                        <th>Revisi</th>
                        <th class="th-a00" style="text-align: center;">A00</th>
                        <th class="th-a00">Tgl Diterima A00</th>
                        <th class="th-a00">Dokumen A00</th>
                        <th class="th-a04" style="text-align: center;">A04</th>
                        <th class="th-a04">Tgl Diterima A04</th>
                        <th class="th-a04">Dokumen A04</th>
                        <th class="th-a05" style="text-align: center;">A05</th>
                        <th class="th-a05">Tgl Diterima A05</th>
                        <th class="th-a05">Dokumen A05</th>
                        <th class="th-partlist" style="text-align: center;">Partlist</th>
                        <th class="th-partlist">Tgl Diterima Partlist</th>
                        <th class="th-partlist">Dokumen Partlist</th>
                        <th class="th-partlist">Revisi Partlist</th>
                        <th class="th-umh" style="text-align: center;">UMH</th>
                        <th class="th-umh">Tgl Diterima UMH</th>
                        <th class="th-umh">Dokumen UMH</th>
                        <th class="th-umh">Revisi UMH</th>
                        <th style="width: 80px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagedRows as $index => $row)
                        @php
                            $rev = $row->revision;
                            $project = $row->project;
                            $costing = $row->costingData;
                            $hasA00 = ($rev->a00 ?? '') === 'ada';
                            $hasA04 = ($rev->a04 ?? '') === 'ada';
                            $hasA05 = ($rev->a05 ?? '') === 'ada';

                            $hasPartlist = (bool) data_get($rev, 'partlist_file_path')
                                || data_get($rev, 'partlist') === 'ada'
                                || (bool) data_get($rev, 'partlist_original_name');

                            $hasUmh = (bool) data_get($rev, 'umh_file_path')
                                || data_get($rev, 'umh') === 'ada'
                                || (bool) data_get($rev, 'umh_original_name');

                            $partlistReceivedDate = data_get($rev, 'partlist_received_date');
                            $umhReceivedDate = data_get($rev, 'umh_received_date');

                            $partlistDocName = data_get($rev, 'partlist_original_name') ?: '';
                            $umhDocName = data_get($rev, 'umh_original_name') ?: '';

                            $partlistRevisionCount = (int) (data_get($rev, 'partlist_revision_count') ?? 0);
                            $umhRevisionCount = (int) (data_get($rev, 'umh_revision_count') ?? 0);

                            $priorityStatus = $row->status;
                        @endphp
                        <tr data-search="{{ strtolower(implode(' ', array_filter([
                            $project->customer ?? '',
                            $costing->customer->name ?? '',
                            $project->model ?? '',
                            $costing->model ?? '',
                            $project->part_name ?? '',
                            $costing->assy_name ?? '',
                            $project->part_number ?? '',
                            $costing->assy_no ?? '',
                            $rev->version_label ?? '',
                        ]))) }}"
                        data-status="{{ $priorityStatus }}">
                            <td>{{ $pagedRows->firstItem() + $loop->index }}</td>
                            <td>{{ $costing->customer->name ?? $project->customer ?? '-' }}</td>
                            <td>{{ $costing->model ?? $project->model ?? '-' }}</td>
                            <td>{{ $costing->assy_name ?? $project->part_name ?? '-' }}</td>
                            <td>{{ $costing->assy_no ?? $project->part_number ?? '-' }}</td>
                            <td>{{ $rev->version_label ?? '-' }}</td>

                            {{-- A00 --}}
                            <td class="td-a00" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA00 ? 'ada' : 'belum' }}">
                                    @if($hasA00)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a00">{{ $hasA00 && $rev->a00_received_date ? $rev->a00_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a00">
                                @if($hasA00 && $rev->a00_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a00']) }}" class="doc-download-link" title="{{ $rev->a00_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a00_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- A04 --}}
                            <td class="td-a04" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA04 ? 'ada' : 'belum' }}">
                                    @if($hasA04)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a04">{{ $hasA04 && $rev->a04_received_date ? $rev->a04_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a04">
                                @if($hasA04 && $rev->a04_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a04']) }}" class="doc-download-link" title="{{ $rev->a04_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a04_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- A05 --}}
                            <td class="td-a05" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasA05 ? 'ada' : 'belum' }}">
                                    @if($hasA05)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-a05">{{ $hasA05 && $rev->a05_received_date ? $rev->a05_received_date->format('d M Y') : '-' }}</td>
                            <td class="td-a05">
                                @if($hasA05 && $rev->a05_document_file_path)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'a05']) }}" class="doc-download-link" title="{{ $rev->a05_document_original_name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($rev->a05_document_original_name, 25) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>

                            {{-- Partlist --}}
                            <td class="td-partlist" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasPartlist ? 'ada' : 'belum' }}">
                                    @if($hasPartlist)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-partlist">
                                @if($hasPartlist && $partlistReceivedDate)
                                    {{ \Carbon\Carbon::parse($partlistReceivedDate)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="td-partlist">
                                @if($hasPartlist)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'partlist']) }}" class="doc-download-link" title="{{ $partlistDocName }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($partlistDocName, 24) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>
                            <td class="td-partlist" style="text-align:center;">
                                {{ $partlistRevisionCount > 0 ? $partlistRevisionCount . 'x' : '-' }}
                            </td>

                            {{-- UMH --}}
                            <td class="td-umh" style="text-align: center;">
                                <span class="doc-status-badge {{ $hasUmh ? 'ada' : 'belum' }}">
                                    @if($hasUmh)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Ada
                                    @else
                                        Belum
                                    @endif
                                </span>
                            </td>
                            <td class="td-umh">
                                @if($hasUmh && $umhReceivedDate)
                                    {{ \Carbon\Carbon::parse($umhReceivedDate)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="td-umh">
                                @if($hasUmh)
                                    <a href="{{ route('tracking-documents.download', [$rev->id, 'umh']) }}" class="doc-download-link" title="{{ $umhDocName }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        {{ Str::limit($umhDocName, 24) }}
                                    </a>
                                @else
                                    <span style="color: var(--slate-400);">-</span>
                                @endif
                            </td>
                            <td class="td-umh" style="text-align:center;">
                                {{ $umhRevisionCount > 0 ? $umhRevisionCount . 'x' : '-' }}
                            </td>
                            {{-- Aksi --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <button type="button" class="btn-action btn-edit js-edit-doc-btn" data-revision-id="{{ $rev->id }}" title="Edit Dokumen"
                                        onclick="openEditDocModal({{ $rev->id }}, {{ json_encode([
                                            'customer' => $costing->customer->name ?? $project->customer ?? '-',
                                            'model' => $costing->model ?? $project->model ?? '-',
                                            'part_name' => $costing->assy_name ?? $project->part_name ?? '-',
                                            'a00' => $rev->a00 ?? '',
                                            'a00_received_date' => $hasA00 && $rev->a00_received_date ? $rev->a00_received_date->format('Y-m-d') : '',
                                            'a00_doc' => $rev->a00_document_original_name ?? '',
                                            'a04' => $rev->a04 ?? '',
                                            'a04_received_date' => $hasA04 && $rev->a04_received_date ? $rev->a04_received_date->format('Y-m-d') : '',
                                            'a04_doc' => $rev->a04_document_original_name ?? '',
                                            'a04_reason' => $rev->a04_reason ?? '',
                                            'a05' => $rev->a05 ?? '',
                                            'a05_received_date' => $hasA05 && $rev->a05_received_date ? $rev->a05_received_date->format('Y-m-d') : '',
                                            'a05_doc' => $rev->a05_document_original_name ?? '',
                                            'partlist' => $rev->partlist ?? '',
                                            'partlist_received_date' => $hasPartlist && $rev->partlist_received_date ? \Carbon\Carbon::parse($rev->partlist_received_date)->format('Y-m-d') : '',
                                            'partlist_doc' => $rev->partlist_original_name ?? '',
                                            'partlist_revision_count' => $rev->partlist_revision_count ?? 0,
                                            'umh' => $rev->umh ?? '',
                                            'umh_received_date' => $hasUmh && $rev->umh_received_date ? \Carbon\Carbon::parse($rev->umh_received_date)->format('Y-m-d') : '',
                                            'umh_doc' => $rev->umh_original_name ?? '',
                                            'umh_revision_count' => $rev->umh_revision_count ?? 0,
                                        ]) }})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-action btn-delete" title="Hapus Dokumen"
                                        onclick="openDeleteDocModal({{ $rev->id }}, '{{ addslashes($costing->assy_name ?? $project->part_name ?? '-') }}')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="24" style="text-align: center; color: var(--slate-400); padding: 2rem;">
                                Belum ada data dokumen project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pagedRows->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div style="font-size: 0.82rem; color: var(--slate-500);">
                Halaman {{ $pagedRows->currentPage() }} dari {{ $pagedRows->lastPage() }}
                &nbsp;·&nbsp; {{ $pagedRows->total() }} data
            </div>
            <div class="doc-pagination">
                {{ $pagedRows->links('pagination.doc-paginator') }}
            </div>
        </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <div id="editDocModal" class="doc-modal is-hidden" onclick="if(event.target===this)closeEditDocModal()">
        <div class="doc-modal-content doc-modal-wide">
            <div class="doc-modal-header">
                <h3 class="doc-modal-title">Edit Project Document</h3>
                <button type="button" class="doc-modal-close" onclick="closeEditDocModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="background: var(--slate-50); padding: 0.6rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.82rem; color: var(--slate-600); border: 1px solid var(--slate-200);">
                <strong id="editDocLabel"></strong>
            </div>
            <form id="editDocForm" method="POST" enctype="multipart/form-data" onsubmit="return validateAndSubmitProjectDocumentForm()">
                @csrf
                @method('PUT')
                @if(session('open_document_revision_id'))
                    <input type="hidden" name="return_to_dashboard" value="1">
                @endif

                <div class="doc-section-grid">
                    {{-- A00 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A00 (RFQ/RFI)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a00" id="editA00Status" class="form-select" onchange="toggleEditDateWrap('a00')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA00DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a00_received_date" id="editA00Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF)</label>
                                <input type="file" name="a00_document_file" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA00DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- A04 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A04 (Cancelled/Failed)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a04" id="editA04Status" class="form-select" onchange="toggleEditDateWrap('a04')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA04DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a04_received_date" id="editA04Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Alasan Canceled/Failed <span style="color:#dc2626;">*</span></label>
                                <textarea name="a04_reason" id="editA04Reason" class="form-input" rows="3" placeholder="Tuliskan alasan project menjadi A04..." style="min-height:84px; resize:vertical;"></textarea>
                                <small style="color: var(--slate-500); font-size: 0.72rem;">Wajib diisi jika status A04 = Ada.</small>
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="a04_document_file" id="editA04File" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA04DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- A05 Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">A05 (Die Go)</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="a05" id="editA05Status" class="form-select" onchange="toggleEditDateWrap('a05')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editA05DateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="a05_received_date" id="editA05Date" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="a05_document_file" id="editA05File" accept=".pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editA05DocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                        </div>
                    </div>

                    {{-- Partlist Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">Partlist</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="partlist" id="editPartlistStatus" class="form-select" onchange="toggleEditDateWrap('partlist')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editPartlistDateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="partlist_received_date" id="editPartlistDate" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (Excel/PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="partlist_document_file" id="editPartlistFile" accept=".xlsx,.xls,.csv,.pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editPartlistDocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                            <div class="doc-form-group">
                                <label>Jumlah Revisi</label>
                                <input type="number" min="0" name="partlist_revision_count" id="editPartlistRevisionCount" class="form-input" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- UMH Column --}}
                    <div class="doc-section-col">
                        <div class="doc-section-title">UMH</div>
                        <div class="doc-form-group">
                            <label>Status</label>
                            <select name="umh" id="editUmhStatus" class="form-select" onchange="toggleEditDateWrap('umh')">
                                <option value="belum_ada">Belum Ada</option>
                                <option value="ada">Ada</option>
                            </select>
                        </div>
                        <div id="editUmhDateWrap" style="display:none;">
                            <div class="doc-form-group">
                                <label>Tanggal Diterima</label>
                                <input type="date" name="umh_received_date" id="editUmhDate" class="form-input">
                            </div>
                            <div class="doc-form-group">
                                <label>Dokumen (Excel/PDF) <span style="color:#dc2626;">*</span></label>
                                <input type="file" name="umh_document_file" id="editUmhFile" accept=".xlsx,.xls,.csv,.pdf" class="form-input" style="font-size:0.75rem;">
                                <small id="editUmhDocName" style="color: var(--slate-500); font-size: 0.72rem;"></small>
                            </div>
                            <div class="doc-form-group">
                                <label>Jumlah Revisi</label>
                                <input type="number" min="0" name="umh_revision_count" id="editUmhRevisionCount" class="form-input" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="doc-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditDocModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteDocModal" class="doc-modal is-hidden" onclick="if(event.target===this)closeDeleteDocModal()">
        <div class="doc-modal-content" style="max-width: 420px;">
            <div class="doc-modal-header">
                <h3 class="doc-modal-title">Konfirmasi Hapus</h3>
                <button type="button" class="doc-modal-close" onclick="closeDeleteDocModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="delete-modal-body">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="margin-bottom: 0.75rem;">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <p class="delete-modal-text">Apakah Anda yakin ingin menghapus semua dokumen (A00, A04, A05) untuk:</p>
                <p class="delete-modal-name" id="deleteDocName"></p>
            </div>
            <form id="deleteDocForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="doc-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteDocModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #ef4444, #dc2626); border-color: #dc2626;">Hapus Dokumen</button>
                </div>
            </form>
        </div>
    </div>
