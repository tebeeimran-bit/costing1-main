@extends('layouts.app')

@section('title', 'Penerimaan Dokumen')
@section('page-title', 'Penerimaan Dokumen')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Penerimaan Dokumen</span>
@endsection

@section('content')
    <style>
        .receipt-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .receipt-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .receipt-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .receipt-form-card.is-hidden {
            display: none;
        }

        .file-hint {
            font-size: 0.75rem;
            color: var(--slate-500);
            margin-top: 0.35rem;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--slate-200);
            border-radius: 0.75rem;
            background: white;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1500px;
        }

        .doc-table th,
        .doc-table td {
            border-bottom: 1px solid var(--slate-200);
            padding: 0.75rem;
            text-align: left;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .doc-table th {
            background: var(--slate-50);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--slate-600);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .receipt-grid {
                grid-template-columns: 1fr;
            }

            .receipt-grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="receipt-page">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-warning">
                <strong>Terdapat kesalahan:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="receipt-list-header">
                <h3 class="card-title" style="margin-bottom: 0;">Riwayat Penerimaan Dokumen</h3>
                <button type="button" id="toggleReceiptFormBtn" class="btn btn-primary" onclick="toggleReceiptForm()">
                    {{ $errors->any() ? 'Tutup Form' : 'Tambah Penerimaan' }}
                </button>
            </div>

            <div class="table-wrap">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th>CUST</th>
                            <th>MODEL</th>
                            <th>PART NUMBER</th>
                            <th>PART NAME</th>
                            <th>PL</th>
                            <th>UMH</th>
                            <th>PIC ENG</th>
                            <th>PIC MKT</th>
                            <th>SEND 1</th>
                            <th>KETERANGAN</th>
                            <th>SEND 2</th>
                            <th>FILE PARTLIST</th>
                            <th>FILE UMH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($receipts as $receipt)
                            <tr>
                                <td>{{ $receipt->cust ?: '-' }}</td>
                                <td>{{ $receipt->model ?: '-' }}</td>
                                <td>{{ $receipt->part_number ?: '-' }}</td>
                                <td>{{ $receipt->part_name ?: '-' }}</td>
                                <td>{{ optional($receipt->pl_date)->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ optional($receipt->umh_date)->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $receipt->pic_eng ?: '-' }}</td>
                                <td>{{ $receipt->pic_mkt ?: '-' }}</td>
                                <td>{{ optional($receipt->send_1_date)->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $receipt->keterangan ?: '-' }}</td>
                                <td>{{ optional($receipt->send_2_date)->format('d/m/Y') ?: '-' }}</td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('document-receipts.download', ['documentReceipt' => $receipt->id, 'type' => 'partlist'], absolute: false) }}"
                                            class="btn btn-secondary">Partlist</a>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('document-receipts.download', ['documentReceipt' => $receipt->id, 'type' => 'umh'], absolute: false) }}"
                                            class="btn btn-secondary">UMH</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" style="text-align: center; color: var(--slate-500);">Belum ada data penerimaan dokumen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="receiptFormCard" class="card receipt-form-card {{ $errors->any() ? '' : 'is-hidden' }}">
            <h3 class="card-title" style="margin-bottom: 1rem;">Form Penerimaan Dokumen Partlist & UMH</h3>

            <form action="{{ route('document-receipts.store', absolute: false) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="receipt-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Nomor Dokumen</label>
                        <input type="text" name="document_number" class="form-input" value="{{ old('document_number') }}"
                            placeholder="Contoh: DR-2026-001">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Penerimaan <span style="color: var(--red-500);">*</span></label>
                        <input type="date" name="received_date" class="form-input"
                            value="{{ old('received_date', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="receipt-grid-3" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">CUST</label>
                        <input type="text" name="cust" class="form-input" value="{{ old('cust') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">MODEL</label>
                        <input type="text" name="model" class="form-input" value="{{ old('model') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PART NUMBER</label>
                        <input type="text" name="part_number" class="form-input" value="{{ old('part_number') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PART NAME</label>
                        <input type="text" name="part_name" class="form-input" value="{{ old('part_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PL</label>
                        <input type="date" name="pl_date" class="form-input" value="{{ old('pl_date') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">UMH</label>
                        <input type="date" name="umh_date" class="form-input" value="{{ old('umh_date') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC ENG</label>
                        <input type="text" name="pic_eng" class="form-input" value="{{ old('pic_eng') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC MKT</label>
                        <input type="text" name="pic_mkt" class="form-input" value="{{ old('pic_mkt') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEND 1</label>
                        <input type="date" name="send_1_date" class="form-input" value="{{ old('send_1_date') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEND 2</label>
                        <input type="date" name="send_2_date" class="form-input" value="{{ old('send_2_date') }}">
                    </div>
                </div>

                <div class="receipt-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Dokumen Partlist <span style="color: var(--red-500);">*</span></label>
                        <input type="file" name="partlist_file" class="form-input" accept=".pdf,.xls,.xlsx" required>
                        <p class="file-hint">Format diizinkan: PDF, XLS, XLSX. Maksimal 10MB.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dokumen UMH <span style="color: var(--red-500);">*</span></label>
                        <input type="file" name="umh_file" class="form-input" accept=".pdf,.xls,.xlsx" required>
                        <p class="file-hint">Format diizinkan: PDF, XLS, XLSX. Maksimal 10MB.</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">KETERANGAN</label>
                    <textarea name="keterangan" class="form-input" rows="3" placeholder="Keterangan (opsional)">{{ old('keterangan') }}</textarea>
                </div>

                <div class="form-actions" style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleReceiptForm() {
            const formCard = document.getElementById('receiptFormCard');
            const toggleButton = document.getElementById('toggleReceiptFormBtn');

            if (!formCard || !toggleButton) {
                return;
            }

            const isHidden = formCard.classList.toggle('is-hidden');
            toggleButton.textContent = isHidden ? 'Tambah Penerimaan' : 'Tutup Form';

            if (!isHidden) {
                formCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    </script>
@endsection
