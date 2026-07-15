@extends('layouts.app')

@section('title', 'Database Customer')
@section('page-title', 'Database Customer')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Customers</span>
@endsection

@section('content')
    @if(session('success'))
        <div
            style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div
            style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fde68a;">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->customerCreate->any())
        <div
            style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            <strong>Terdapat kesalahan saat tambah customer:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                @foreach($errors->customerCreate->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
        <button type="button" class="btn-primary" onclick="openAddCustomerModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tambah Customer
        </button>
    </div>

    <div id="add-customer-modal" class="customer-modal {{ $errors->customerCreate->any() ? '' : 'is-hidden' }}" onclick="handleCustomerModalOverlay(event)">
        <div class="customer-modal-content">
            <div class="customer-modal-header">
                <h3 class="customer-modal-title">Tambah Customer</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeAddCustomerModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('database.customers.store', absolute: false) }}" method="POST" class="customer-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Code Customer <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="code" class="form-input" value="{{ old('code') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Customer <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
                </div>
                <div class="customer-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddCustomerModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Customer</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-customer-modal" class="customer-modal is-hidden" onclick="handleCustomerModalOverlay(event)">
        <div class="customer-modal-content">
            <div class="customer-modal-header">
                <h3 class="customer-modal-title">Edit Customer</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeEditCustomerModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="edit-customer-form" method="POST" class="customer-form">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Code Customer <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-customer-code" name="code" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Customer <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-customer-name" name="name" class="form-input" required>
                </div>
                <div class="customer-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditCustomerModal()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-customer-modal" class="customer-modal is-hidden" onclick="handleCustomerModalOverlay(event)">
        <div class="customer-modal-content delete-modal-content">
            <div class="customer-modal-header">
                <h3 class="customer-modal-title">Konfirmasi Hapus</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeDeleteCustomerModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="delete-modal-body">
                <p class="delete-modal-text">
                    Kamu yakin ingin menghapus customer
                    <strong id="delete-customer-name"></strong>?
                </p>
                <p class="delete-modal-subtext">Data yang sudah dipakai pada costing tidak bisa dihapus.</p>
                <div class="customer-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeDeleteCustomerModal()">Batal</button>
                    <button type="button" class="btn-danger" onclick="submitDeleteCustomerForm()">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <div class="material-table-container" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Code Customer</th>
                    <th>Nama Customer</th>
                    <th style="width: 100px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $index => $customer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $customer->code }}</td>
                        <td>{{ $customer->name }}</td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-action btn-edit" title="Edit" onclick="openEditCustomerModal({{ $customer->id }}, '{{ $customer->code }}', '{{ $customer->name }}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    style="width: 16px; height: 16px;">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </button>
                            <form id="delete-customer-form-{{ $customer->id }}" action="{{ route('database.customers.destroy', ['id' => $customer->id], absolute: false) }}" method="POST" style="display: inline-flex;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action btn-delete" title="Hapus" onclick="openDeleteCustomerModal({{ $customer->id }}, @js($customer->name))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        style="width: 16px; height: 16px;">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2-2v2" />
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">Tidak ada customer ditemukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--blue-700) 0%, var(--blue-800) 100%);
            transform: translateY(-2px);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--blue-100);
            color: var(--blue-600);
        }

        .btn-edit:hover {
            background: var(--blue-200);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #eef2ff;
            border-color: #94a3b8;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border: 1px solid #dc2626;
            background: #dc2626;
            color: #fff;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        .customer-modal {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            padding: 1rem;
        }

        .customer-modal.is-hidden {
            display: none;
        }

        .customer-modal-content {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 0.8rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }

        .customer-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .customer-modal-title {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }

        .customer-form {
            padding: 1rem 1.1rem 1.1rem;
        }

        .form-group {
            margin-bottom: 0.9rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.6rem 0.7rem;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .form-input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
        }

        .customer-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.55rem;
            margin-top: 1rem;
        }

        .delete-modal-content {
            width: min(460px, 100%);
        }

        .delete-modal-body {
            padding: 1rem 1.1rem 1.2rem;
        }

        .delete-modal-text {
            margin: 0;
            font-size: 0.95rem;
            color: #0f172a;
            line-height: 1.5;
        }

        .delete-modal-subtext {
            margin: 0.35rem 0 0;
            font-size: 0.82rem;
            color: #64748b;
        }
    </style>

    <script>
        let deleteCustomerFormId = null;

        function openAddCustomerModal() {
            const modal = document.getElementById('add-customer-modal');
            if (modal) {
                modal.classList.remove('is-hidden');
            }
        }

        function closeAddCustomerModal() {
            const modal = document.getElementById('add-customer-modal');
            if (modal) {
                modal.classList.add('is-hidden');
            }
        }

        function openEditCustomerModal(id, code, name) {
            const modal = document.getElementById('edit-customer-modal');
            const form = document.getElementById('edit-customer-form');
            const codeInput = document.getElementById('edit-customer-code');
            const nameInput = document.getElementById('edit-customer-name');

            if (modal && form && codeInput && nameInput) {
                codeInput.value = code;
                nameInput.value = name;
                form.action = `/database/customers/${id}`;
                modal.classList.remove('is-hidden');
            }
        }

        function closeEditCustomerModal() {
            const modal = document.getElementById('edit-customer-modal');
            if (modal) {
                modal.classList.add('is-hidden');
            }
        }

        function openDeleteCustomerModal(id, name) {
            const modal = document.getElementById('delete-customer-modal');
            const nameEl = document.getElementById('delete-customer-name');

            deleteCustomerFormId = `delete-customer-form-${id}`;
            if (nameEl) {
                nameEl.textContent = name;
            }

            if (modal) {
                modal.classList.remove('is-hidden');
            }
        }

        function closeDeleteCustomerModal() {
            const modal = document.getElementById('delete-customer-modal');
            if (modal) {
                modal.classList.add('is-hidden');
            }
            deleteCustomerFormId = null;
        }

        function submitDeleteCustomerForm() {
            if (!deleteCustomerFormId) {
                return;
            }

            const form = document.getElementById(deleteCustomerFormId);
            if (form) {
                form.submit();
            }
        }

        function handleCustomerModalOverlay(event) {
            if (event.target && (event.target.id === 'add-customer-modal' || event.target.id === 'edit-customer-modal' || event.target.id === 'delete-customer-modal')) {
                if (event.target.id === 'add-customer-modal') {
                    closeAddCustomerModal();
                } else if (event.target.id === 'edit-customer-modal') {
                    closeEditCustomerModal();
                } else {
                    closeDeleteCustomerModal();
                }
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const addModal = document.getElementById('add-customer-modal');
                const editModal = document.getElementById('edit-customer-modal');
                const deleteModal = document.getElementById('delete-customer-modal');
                if (addModal && !addModal.classList.contains('is-hidden')) {
                    closeAddCustomerModal();
                }
                if (editModal && !editModal.classList.contains('is-hidden')) {
                    closeEditCustomerModal();
                }
                if (deleteModal && !deleteModal.classList.contains('is-hidden')) {
                    closeDeleteCustomerModal();
                }
            }
        });
    </script>
@endsection