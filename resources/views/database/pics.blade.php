@extends('layouts.app')

@section('title', 'Database PIC')
@section('page-title', 'Database PIC')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>PIC</span>
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

    <div id="add-pic-modal" class="pic-modal is-hidden" onclick="handleAddPicModalOverlay(event)">
        <div class="pic-modal-content">
            <div class="pic-modal-header">
                <h3 class="pic-modal-title">Tambah PIC</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeAddPicModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('database.pics.store', absolute: false) }}" method="POST" class="pic-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nama PIC <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe PIC <span style="color: #dc2626;">*</span></label>
                    <select name="type" class="form-input" required>
                        <option value="engineering">Engineering</option>
                        <option value="marketing">Marketing</option>
                    </select>
                </div>
                <div class="pic-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddPicModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-pic-modal" class="pic-modal is-hidden" onclick="handlePicModalOverlay(event)">
        <div class="pic-modal-content">
            <div class="pic-modal-header">
                <h3 class="pic-modal-title">Edit PIC</h3>
                <button type="button" class="btn-action btn-edit" onclick="closeEditPicModal()" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="edit-pic-form" method="POST" class="pic-form">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nama PIC <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-pic-name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe PIC <span style="color: #dc2626;">*</span></label>
                    <select id="edit-pic-type" name="type" class="form-input" required>
                        <option value="engineering">Engineering</option>
                        <option value="marketing">Marketing</option>
                    </select>
                </div>
                <div class="pic-form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditPicModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar PIC Engineering & Marketing</h3>
            <button type="button" class="btn btn-primary" onclick="openAddPicModal()" style="margin-left: auto;">
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
                        <th>Nama PIC</th>
                        <th style="width: 220px;">Tipe PIC</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pics as $index => $pic)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $pic->name }}</td>
                            <td>{{ ucfirst($pic->type) }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-action btn-edit" title="Edit"
                                    onclick="openEditPicModal({{ $pic->id }}, @js($pic->name), @js($pic->type))">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        style="width: 16px; height: 16px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <form action="{{ route('database.pics.destroy', ['id' => $pic->id], absolute: false) }}" method="POST" style="display: inline;" class="js-confirm-form" data-confirm-message="Hapus PIC ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center;">Belum ada data PIC.</td>
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
            background: #eef2ff;
            border-color: #94a3b8;
        }

        .pic-modal {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            padding: 1rem;
        }

        .pic-modal.is-hidden {
            display: none;
        }

        .pic-modal-content {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 0.8rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }

        .pic-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .pic-modal-title {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }

        .pic-form {
            padding: 1rem 1.1rem 1.1rem;
        }

        .form-group {
            margin-bottom: 0.9rem;
        }

        .pic-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 1.2rem;
        }
    </style>

    <script>
        function openAddPicModal() {
            const modal = document.getElementById('add-pic-modal');
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAddPicModal() {
            const modal = document.getElementById('add-pic-modal');
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';
        }

        function handleAddPicModalOverlay(event) {
            if (event.target.id === 'add-pic-modal') {
                closeAddPicModal();
            }
        }

        function openEditPicModal(id, name, type) {
            const modal = document.getElementById('edit-pic-modal');
            const form = document.getElementById('edit-pic-form');
            const nameInput = document.getElementById('edit-pic-name');
            const typeInput = document.getElementById('edit-pic-type');

            form.action = `{{ route('database.pics', absolute: false) }}/${id}`;
            nameInput.value = name;
            typeInput.value = type;
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEditPicModal() {
            const modal = document.getElementById('edit-pic-modal');
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';
        }

        function handlePicModalOverlay(event) {
            if (event.target.id === 'edit-pic-modal') {
                closeEditPicModal();
            }
        }
    </script>
@endsection
