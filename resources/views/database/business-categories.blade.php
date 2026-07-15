@extends('layouts.app')

@section('title', 'Database Business Categories')
@section('page-title', 'Database Business Categories')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Business Categories</span>
@endsection

@section('content')
    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div id="add-bc-modal" class="bc-modal is-hidden" onclick="handleBcModalOverlay(event)">
        <div class="bc-modal-content">
            <div class="bc-modal-header">
                <h3 class="bc-modal-title" id="bc-modal-title">Tambah Business Category</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeEditBcModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="add-bc-form" method="POST" class="bc-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Kode <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="bc-code" name="code" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Business Category <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="bc-name" name="name" class="form-input" required>
                </div>
                <div class="bc-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditBcModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Business Categories</h3>
            <button type="button" class="btn btn-primary" onclick="openAddBcModal()" style="margin-left: auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem; display: inline;">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Tambah
            </button>
        </div>

        <div class="material-table-container" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">No.</th>
                        <th style="width: 200px;">Kode</th>
                        <th>Nama Business Category</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($businessCategories as $index => $businessCategory)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $businessCategory->code }}</td>
                            <td>{{ $businessCategory->name }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                    onclick="openEditBcModal({{ $businessCategory->id }}, @js($businessCategory->code), @js($businessCategory->name))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <form action="{{ route('database.business-categories.destroy', ['id' => $businessCategory->id], absolute: false) }}" method="POST" style="display: inline;" class="js-confirm-form" data-confirm-message="Hapus business category ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            style="width: 16px; height: 16px;">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center;">Belum ada business category.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
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
            background: #e2e8f0;
        }

        .bc-modal {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .bc-modal.is-hidden {
            display: none;
        }

        .bc-modal-content {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .bc-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .bc-modal-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }

        .bc-form {
            padding: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-input {
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .bc-form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
    </style>

    <script>
        function openAddBcModal() {
            const modal = document.getElementById('add-bc-modal');
            const form = document.getElementById('add-bc-form');
            const titleEl = document.getElementById('bc-modal-title');
            const codeInput = document.getElementById('bc-code');
            const input = document.getElementById('bc-name');
            
            form.action = '{{ route('database.business-categories.store', absolute: false) }}';
            form.method = 'POST';
            titleEl.textContent = 'Tambah Business Category';
            codeInput.value = '';
            input.value = '';
            
            // Remove the PUT method if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) {
                methodInput.remove();
            }
            
            modal.classList.remove('is-hidden');
            codeInput.focus();
        }

        function openEditBcModal(id, code, name) {
            const modal = document.getElementById('add-bc-modal');
            const form = document.getElementById('add-bc-form');
            const titleEl = document.getElementById('bc-modal-title');
            const codeInput = document.getElementById('bc-code');
            const input = document.getElementById('bc-name');
            
            form.action = `{{ route('database.business-categories.update', ['id' => '__ID__'], absolute: false) }}`
                .replace('__ID__', id);
            titleEl.textContent = 'Edit Business Category';
            codeInput.value = code;
            input.value = name;
            
            // Add or update the PUT method
            let methodInput = form.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                form.appendChild(methodInput);
            }
            methodInput.value = 'PUT';
            
            modal.classList.remove('is-hidden');
            codeInput.focus();
        }

        function closeEditBcModal() {
            const modal = document.getElementById('add-bc-modal');
            modal.classList.add('is-hidden');
        }

        function handleBcModalOverlay(event) {
            if (event.target === event.currentTarget) {
                closeEditBcModal();
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const modal = document.getElementById('add-bc-modal');
                if (modal && !modal.classList.contains('is-hidden')) {
                    closeEditBcModal();
                }
            }
        });
    </script>
@endsection
