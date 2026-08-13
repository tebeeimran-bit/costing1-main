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

    @if(session('error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->importWires->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat import wire:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->importWires->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('wireImportIssues'))
        <div style="background: #fff7ed; color: #9a3412; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fed7aa;">
            <strong>Detail baris gagal (maks 30):</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach((array) session('wireImportIssues') as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->wireCreate->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat tambah wire:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->wireCreate->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->wireEdit->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat edit wire:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->wireEdit->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->wireRateCreate->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat tambah rates:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->wireRateCreate->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->wireRateEdit->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat edit rates:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->wireRateEdit->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <h3 class="card-title" style="margin: 0;">Rates</h3>
            <button type="button" class="btn-primary" onclick="openAddRateModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Tambah Rates
            </button>
        </div>
        <div style="padding: 1rem;">
            @if($rateColumns->isEmpty())
                <div style="color: #64748b;">Belum ada data rates.</div>
            @else
                <div class="material-table-container">
                    <table class="rates-matrix">
                        <tbody>
                            <tr>
                                <th class="rates-currency">JPY</th>
                                @foreach($rateColumns as $rate)
                                    <td class="rates-number">{{ $formatMax5($rate->jpy_rate) }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="rates-currency">USD</th>
                                @foreach($rateColumns as $rate)
                                    <td class="rates-number">{{ $formatMax5($rate->usd_rate) }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="rates-spacer"></th>
                                @foreach($rateColumns as $rate)
                                    <th class="rates-month">{{ $rate->period_month ? $rate->period_month->format('M-y') : '-' }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="rates-spacer"></th>
                                @foreach($rateColumns as $rate)
                                    <th class="rates-lme-title">LME YANG BERLAKU</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="rates-spacer"></th>
                                @foreach($rateColumns as $rate)
                                    <td class="rates-lme-active">{{ $formatMax5($rate->lme_active) }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="rates-spacer"></th>
                                @foreach($rateColumns as $rate)
                                    <td class="rates-lme-reference">{{ $formatMax5($rate->lme_reference) }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="material-table-container" style="padding: 0 1rem 1rem;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Period/Request</th>
                        <th>JPY</th>
                        <th>USD</th>
                        <th>LME Yang Berlaku</th>
                        <th>LME Referensi</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wireRates as $rate)
                        <tr>
                            <td>{{ $rate->period_month ? $rate->period_month->format('M-y') : ($rate->request_name ?: '-') }}</td>
                            <td>{{ $formatMax5($rate->jpy_rate) }}</td>
                            <td>{{ $formatMax5($rate->usd_rate) }}</td>
                            <td>{{ $formatMax5($rate->lme_active) }}</td>
                            <td>{{ $formatMax5($rate->lme_reference) }}</td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                    onclick="openEditRateModal({{ $rate->id }}, @js($rate->period_month ? $rate->period_month->format('Y-m') : ''), @js($rate->request_name ?? ''), @js((string) $rate->jpy_rate), @js((string) $rate->usd_rate), @js((string) $rate->lme_active), @js((string) $rate->lme_reference))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <button type="button" class="btn-action btn-delete" title="Hapus"
                                    onclick="openDeleteRateModal({{ $rate->id }}, @js($rate->period_month ? $rate->period_month->format('M-y') : ($rate->request_name ?: '-')))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center;">Belum ada data rates.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-rate-modal" class="wire-modal {{ $errors->wireRateCreate->any() ? '' : 'is-hidden' }}" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Tambah Rates</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeAddRateModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('database.wires.rates.store', absolute: false) }}" method="POST" class="wire-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Period (Bulan)</label>
                    <input type="month" name="period_month" class="form-input" value="{{ old('period_month') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Request Khusus</label>
                    <input type="text" name="request_name" class="form-input" value="{{ old('request_name') }}" maxlength="255" placeholder="Contoh: Request RFQ-001">
                    <small style="color: #64748b; font-size: 0.8rem;">Isi salah satu: Period atau Request Khusus.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">JPY <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" name="jpy_rate" class="form-input raw-rate-input" value="{{ old('jpy_rate', 0) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">USD <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" name="usd_rate" class="form-input raw-rate-input" value="{{ old('usd_rate', 0) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">LME Yang Berlaku <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="add-rate-active" name="lme_active" class="form-input raw-rate-input" value="{{ old('lme_active', 0) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">LME Referensi <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="add-rate-reference" name="lme_reference" class="form-input raw-rate-input" value="{{ old('lme_reference', 0) }}" readonly required>
                </div>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddRateModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Rates</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-rate-modal" class="wire-modal is-hidden" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Edit Rates</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeEditRateModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="edit-rate-form" method="POST" class="wire-form" data-action-template="{{ route('database.wires.rates.update', ['id' => '__ID__'], absolute: false) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Period (Bulan)</label>
                    <input type="month" id="edit-rate-period" name="period_month" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Request Khusus</label>
                    <input type="text" id="edit-rate-request-name" name="request_name" class="form-input" maxlength="255" placeholder="Contoh: Request RFQ-001">
                    <small style="color: #64748b; font-size: 0.8rem;">Isi salah satu: Period atau Request Khusus.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">JPY <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="edit-rate-jpy" name="jpy_rate" class="form-input raw-rate-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">USD <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="edit-rate-usd" name="usd_rate" class="form-input raw-rate-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">LME Yang Berlaku <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="edit-rate-active" name="lme_active" class="form-input raw-rate-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">LME Referensi <span style="color: #dc2626;">*</span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" id="edit-rate-reference" name="lme_reference" class="form-input raw-rate-input" readonly required>
                </div>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditRateModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-rate-modal" class="wire-modal is-hidden" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Konfirmasi Hapus Rates</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeDeleteRateModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div style="padding: 1.25rem;">
                <p style="margin: 0 0 0.5rem; color: #0f172a;">Kamu yakin ingin menghapus rates <strong id="delete-rate-period-text"></strong>?</p>
                <p style="margin: 0 0 1rem; color: #64748b; font-size: 0.9rem;">Data yang dihapus tidak bisa dikembalikan.</p>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeDeleteRateModal()">Batal</button>
                    <button type="button" class="btn-danger" onclick="submitDeleteRateForm()">Ya, Hapus</button>
                </div>
            </div>
            <form id="delete-rate-form" method="POST" data-action-template="{{ route('database.wires.rates.destroy', ['id' => '__ID__'], absolute: false) }}" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="wire-rate-month-selector" style="font-weight: 600; color: #0f172a;">Rate Aktif:</label>
            <form id="wire-month-selector-form" method="POST" action="{{ route('database.wires.switch-rate-month', absolute: false) }}" style="display: flex; gap: 0.5rem;">
                @csrf
                <select id="wire-rate-month-selector" name="rate_id" class="form-input" onchange="document.getElementById('wire-month-selector-form').submit()" style="padding: 0.5rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; min-width: 180px;">
                    @forelse($wireRates as $rate)
                        <option value="{{ $rate->id }}" {{ ((int) ($selectedRateId ?? 0) === (int) $rate->id) ? 'selected' : '' }}>
                            {{ $rate->period_month ? $rate->period_month->format('M-Y') : ($rate->request_name ?: 'Request Khusus') }}
                        </option>
                    @empty
                        <option value="">Tidak ada rate</option>
                    @endforelse
                </select>
            </form>
            @if($activeRate)
                <div style="padding: 0.5rem 0.75rem; background: #f0fdf4; border-radius: 0.375rem; border: 1px solid #86efac; color: #166534; font-size: 0.875rem;">
                    JPY: {{ $formatMax5($activeRate->jpy_rate) }} | USD: {{ $formatMax5($activeRate->usd_rate) }} | LME: {{ $formatMax5($activeRate->lme_active) }}
                </div>
            @endif
        </div>

        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
            <a href="{{ route('database.wires.template', absolute: false) }}" class="btn-secondary">
                Download Template Excel
            </a>
            <button type="button" class="btn-secondary" onclick="openImportWireModal()">
                Update via Excel
            </button>
            <button type="button" class="btn-primary" onclick="openAddWireModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Tambah Wire
            </button>
        </div>
    </div>

    <div id="import-wire-modal" class="wire-modal {{ $errors->importWires->any() ? '' : 'is-hidden' }}" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content" style="width: min(560px, 100%);">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Update Wire via Excel</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeImportWireModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('database.wires.import', absolute: false) }}" method="POST" enctype="multipart/form-data" class="wire-form">
                @csrf
                <div style="font-size: 0.86rem; color: #475569; line-height: 1.45;">
                    Gunakan file .xlsx sesuai template. Update dilakukan berdasarkan kolom <strong>item</strong>, bukan idcode.
                </div>
                <input type="file" name="import_file" accept=".xlsx" required class="form-input" style="padding: 0.6rem;">
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeImportWireModal()">Batal</button>
                    <button type="submit" class="btn-primary">Import Excel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="add-wire-modal" class="wire-modal {{ $errors->wireCreate->any() ? '' : 'is-hidden' }}" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Tambah Wire</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeAddWireModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('database.wires.store', absolute: false) }}" method="POST" class="wire-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Idcode <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="idcode" class="form-input" value="{{ old('idcode') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Item <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="item" class="form-input" value="{{ old('item') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Machine Maintenance <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="machine_maintenance" class="form-input wire-decimal-input" value="{{ old('machine_maintenance') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fix Cost <span style="color: #dc2626;">*</span></label>
                    <input type="number" step="0.00001" min="0" name="fix_cost" class="form-input wire-number-input" value="{{ old('fix_cost', 0) }}" required>
                </div>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddWireModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Wire</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-wire-modal" class="wire-modal is-hidden" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Edit Wire</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeEditWireModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="edit-wire-form" method="POST" class="wire-form" data-action-template="{{ route('database.wires.update', ['id' => '__ID__'], absolute: false) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Idcode <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-wire-idcode" name="idcode" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Item <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-wire-item" name="item" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Machine Maintenance <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="edit-wire-machine-maintenance" name="machine_maintenance" class="form-input wire-decimal-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fix Cost <span style="color: #dc2626;">*</span></label>
                        <input type="number" id="edit-wire-fix-cost" step="0.00001" min="0" name="fix_cost" class="form-input wire-number-input" required>
                </div>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditWireModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-wire-modal" class="wire-modal is-hidden" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Konfirmasi Hapus</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeDeleteWireModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div style="padding: 1.25rem;">
                <p style="margin: 0 0 0.5rem; color: #0f172a;">Kamu yakin ingin menghapus wire <strong id="delete-wire-idcode-text"></strong>?</p>
                <p style="margin: 0 0 1rem; color: #64748b; font-size: 0.9rem;">Data yang dihapus tidak bisa dikembalikan.</p>
                <div class="wire-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeDeleteWireModal()">Batal</button>
                    <button type="button" class="btn-danger" onclick="submitDeleteWireForm()">Ya, Hapus</button>
                </div>
            </div>
            <form id="delete-wire-form" method="POST" data-action-template="{{ route('database.wires.destroy', ['id' => '__ID__'], absolute: false) }}" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Wire</h3>
        </div>
        <div class="material-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Idcode</th>
                        <th>Item</th>
                        <th>Machine Maintenance</th>
                        <th>Fix Cost</th>
                        <th>Price</th>
                        <th style="width: 280px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wires as $index => $wire)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $wire->idcode }}</td>
                            <td>{{ $wire->item }}</td>
                            <td>{{ $wire->machine_maintenance }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $wire->fix_cost, 5, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $wire->price, 5, '.', ''), '0'), '.') }}</td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="btn-action btn-edit" title="Notes"
                                    onclick="openPriceNotesModal({{ $wire->id }}, @js($wire->idcode), @js($wire->item), @js((string) $wire->price))">
                                    Notes
                                </button>
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                    onclick="openEditWireModal({{ $wire->id }}, @js($wire->idcode), @js($wire->item), @js($wire->machine_maintenance), @js((string) $wire->fix_cost), @js((string) $wire->price))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <button type="button" class="btn-action btn-delete" title="Hapus"
                                    onclick="openDeleteWireModal({{ $wire->id }}, @js($wire->idcode))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center;">Belum ada data wire.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="price-notes-modal" class="wire-modal is-hidden" onclick="handleWireModalOverlay(event)">
        <div class="wire-modal-content" style="width: min(760px, 100%);">
            <div class="wire-modal-header">
                <h3 class="wire-modal-title">Notes Perhitungan Price</h3>
                <button type="button" class="btn-action btn-edit" onclick="closePriceNotesModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div style="padding: 1rem 1.25rem 1.25rem;">
                <p style="margin: 0 0 0.35rem; color: #0f172a;"><strong id="notes-wire-idcode"></strong> - <span id="notes-wire-item"></span></p>
                <p style="margin: 0 0 1rem; color: #64748b; font-size: 0.9rem;">Price tersimpan: <strong id="notes-current-price"></strong></p>

                <div id="notes-error-box" class="is-hidden" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 0.5rem; padding: 0.75rem;"></div>

                <div id="notes-detail-box" class="is-hidden">
                    <table class="data-table" style="margin-bottom: 0.75rem;">
                        <tbody>
                            <tr><th style="width: 45%;">Rate Aktif</th><td id="notes-rate-label"></td></tr>
                            <tr><th>USD Rate</th><td id="notes-usd-rate"></td></tr>
                            <tr><th>LME Yang Berlaku</th><td id="notes-lme-active"></td></tr>
                            <tr><th>LME Referensi</th><td id="notes-lme-reference"></td></tr>
                            <tr><th>Lookup Value</th><td id="notes-lookup-value"></td></tr>
                            <tr><th>Machine Maintenance</th><td id="notes-machine-maintenance"></td></tr>
                            <tr><th>Fix Cost</th><td id="notes-fix-cost"></td></tr>
                            <tr><th>Base Value</th><td id="notes-base-value"></td></tr>
                            <tr><th id="notes-rounding-label">Round Up (ceil)</th><td id="notes-rounded-value"></td></tr>
                            <tr><th>Markup Factor</th><td id="notes-markup-factor"></td></tr>
                            <tr><th>Final Price</th><td id="notes-final-price"></td></tr>
                        </tbody>
                    </table>

                    <div id="notes-formula-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem; color: #334155; font-size: 0.9rem;">
                        Rumus: <strong id="notes-formula-text">ROUNDUP((((Lookup + Machine Maintenance) * USD) + Fix Cost), 0) * 1.03</strong>
                    </div>
                </div>

                <div class="wire-form-actions" style="margin-top: 1rem;">
                    <button type="button" class="btn-secondary" onclick="closePriceNotesModal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>
