@extends('layouts.app')

@section('title', 'Database Cycle Time')
@section('page-title', 'Database Cycle Time Template')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Cycle Time</span>
@endsection

@section('content')
    @if(session('success'))
        <div
            style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div
            style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div id="add-cycle-time-modal" class="cycle-time-modal is-hidden" onclick="handleAddCycleTimeModalOverlay(event)">
        <div class="cycle-time-modal-content">
            <div class="cycle-time-modal-header">
                <h3 class="cycle-time-modal-title">Tambah Process Template</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeAddCycleTimeModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('database.cycle-time-templates.store', absolute: false) }}" method="POST" class="cycle-time-form">
                @csrf
                <div>
                    <label class="form-label">Process <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="process" class="form-input" placeholder="Contoh: Cutting, Stripping" required>
                </div>
                <div class="cycle-time-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddCycleTimeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Process Cycle Time</h3>
            <button type="button" class="btn btn-primary" onclick="openAddCycleTimeModal()" style="margin-left: auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 0.5rem; display: inline;">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Tambah
            </button>
        </div>

        <div id="edit-cycle-time-modal" class="cycle-time-modal is-hidden" onclick="handleCycleTimeModalOverlay(event)">
            <div class="cycle-time-modal-content">
                <div class="cycle-time-modal-header">
                    <h3 class="cycle-time-modal-title">Edit Process Template</h3>
                    <button type="button" class="btn-action btn-edit" onclick="closeEditCycleTimeModal()" aria-label="Tutup">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <form id="edit-cycle-time-form" method="POST" class="cycle-time-form">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="form-label">Process <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="edit-cycle-time-process" name="process" class="form-input" required>
                    </div>
                    <div class="cycle-time-form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditCycleTimeModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="material-table-container" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">No.</th>
                        <th>Process</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $index => $template)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $template->process }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                    onclick="openEditCycleTimeModal({{ $template->id }}, @js($template->process))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <form
                                    action="{{ route('database.cycle-time-templates.destroy', $template->id, false) }}"
                                    method="POST" style="display: inline;" class="js-confirm-form"
                                    data-confirm-message="Hapus process template ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            style="width: 16px; height: 16px;">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path
                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center;">Belum ada process template.</td>
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

        .cycle-time-modal {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            padding: 1rem;
        }

        .cycle-time-modal.is-hidden {
            display: none;
        }

        .cycle-time-modal-content {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 0.8rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }

        .cycle-time-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .cycle-time-modal-title {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }

        .cycle-time-form {
            padding: 1rem 1.1rem 1.1rem;
        }

        .cycle-time-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 1rem;
        }
    </style>

    <script>
        function openAddCycleTimeModal() {
            const modal = document.getElementById('add-cycle-time-modal');
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAddCycleTimeModal() {
            const modal = document.getElementById('add-cycle-time-modal');
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';
        }

        function handleAddCycleTimeModalOverlay(event) {
            if (event.target.id === 'add-cycle-time-modal') {
                closeAddCycleTimeModal();
            }
        }

        function openEditCycleTimeModal(id, process) {
            const modal = document.getElementById('edit-cycle-time-modal');
            const form = document.getElementById('edit-cycle-time-form');
            const processInput = document.getElementById('edit-cycle-time-process');

            form.action = `{{ route('database.cycle-time-templates', absolute: false) }}/${id}`;
            processInput.value = process;
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEditCycleTimeModal() {
            const modal = document.getElementById('edit-cycle-time-modal');
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';
        }

        function handleCycleTimeModalOverlay(event) {
            if (event.target.id === 'edit-cycle-time-modal') {
                closeEditCycleTimeModal();
            }
        }
    </script>
@endsection
