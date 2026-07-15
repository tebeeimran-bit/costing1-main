@extends('layouts.app')

@section('title', 'Costing Assistant Training')
@section('page-title', 'Costing Assistant Training')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Costing Assistant Training</span>
@endsection

@section('content')
<div class="assistant-training-page">
    @if(session('success'))
        <div class="assistant-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="assistant-alert danger">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="assistant-training-hero">
        <div>
            <h2>Knowledge, Rules, dan File Templates</h2>
            <p>Semua training di sini lokal dan deterministik. Tidak ada data yang dikirim ke AI/API luar.</p>
        </div>
        <div class="assistant-training-metrics">
            <span><strong>{{ $topics->count() }}</strong> Topics</span>
            <span><strong>{{ $rules->count() }}</strong> Rules</span>
            <span><strong>{{ $templates->count() }}</strong> Templates</span>
        </div>
    </section>

    <div class="assistant-training-grid">
        <section class="assistant-training-card">
            <h3>Tambah Topic / FAQ</h3>
            <form method="POST" action="{{ route('assistant.topics.store', absolute: false) }}" class="assistant-training-form">
                @csrf
                <label>Menu
                    <input name="menu" value="general" required>
                </label>
                <label>Judul
                    <input name="title" required placeholder="Contoh: Kenapa costing belum bisa submit?">
                </label>
                <label>Role khusus
                    <select name="role">
                        <option value="">Semua role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Keywords
                    <textarea name="keywords_text" rows="3" placeholder="submit, approval, unpriced"></textarea>
                </label>
                <label>Jawaban / Panduan
                    <textarea name="content" rows="5" required></textarea>
                </label>
                <label class="assistant-check-row"><input type="checkbox" name="active" value="1" checked> Aktif</label>
                <button type="submit" class="assistant-admin-btn primary">Simpan Topic</button>
            </form>
        </section>

        <section class="assistant-training-card">
            <h3>Tambah Rule</h3>
            <form method="POST" action="{{ route('assistant.rules.store', absolute: false) }}" class="assistant-training-form">
                @csrf
                <label>Kode
                    <input name="code" required placeholder="missing_rate_custom">
                </label>
                <label>Judul
                    <input name="title" required>
                </label>
                <label>Kondisi
                    <select name="condition_type" required>
                        @foreach($conditionTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Payload JSON
                    <textarea name="condition_payload_text" rows="3" placeholder='{"keywords":["submit"],"count":0}'></textarea>
                </label>
                <label>Severity
                    <select name="severity" required>
                        @foreach($severityOptions as $severity)
                            <option value="{{ $severity }}">{{ $severity }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Pesan
                    <textarea name="message" rows="4" required></textarea>
                </label>
                <div class="assistant-form-row">
                    <label>Action Label<input name="action_label"></label>
                    <label>Action URL<input name="action_url" placeholder="/project"></label>
                </div>
                <label class="assistant-check-row"><input type="checkbox" name="active" value="1" checked> Aktif</label>
                <button type="submit" class="assistant-admin-btn primary">Simpan Rule</button>
            </form>
        </section>

        <section class="assistant-training-card">
            <h3>Tambah File Template</h3>
            <form method="POST" action="{{ route('assistant.templates.store', absolute: false) }}" class="assistant-training-form">
                @csrf
                <label>Tipe
                    <select name="type">
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </label>
                <label>Nama Template
                    <input name="name" required placeholder="Database Parts Excel">
                </label>
                <label>Kolom Wajib
                    <textarea name="required_columns_text" rows="3" placeholder="material_code&#10;price&#10;currency"></textarea>
                </label>
                <label>Kolom Opsional
                    <textarea name="optional_columns_text" rows="3"></textarea>
                </label>
                <label>Validation Rules JSON
                    <textarea name="validation_rules_text" rows="3" placeholder='{"unique_by":"material_code","max_size_mb":20}'></textarea>
                </label>
                <label class="assistant-check-row"><input type="checkbox" name="active" value="1" checked> Aktif</label>
                <button type="submit" class="assistant-admin-btn primary">Simpan Template</button>
            </form>
        </section>
    </div>

    <section class="assistant-training-list">
        <h3>Topics / FAQ</h3>
        @forelse($topics as $topic)
            <details class="assistant-training-item">
                <summary><span>{{ $topic->title }}</span><em>{{ $topic->menu }}{{ $topic->role ? ' / ' . $topic->role : '' }}</em></summary>
                <form method="POST" action="{{ route('assistant.topics.update', $topic, false) }}" class="assistant-training-form compact">
                    @csrf @method('PUT')
                    <div class="assistant-form-row"><label>Menu<input name="menu" value="{{ $topic->menu }}" required></label><label>Role<input name="role" value="{{ $topic->role }}"></label></div>
                    <label>Judul<input name="title" value="{{ $topic->title }}" required></label>
                    <label>Keywords<textarea name="keywords_text" rows="2">{{ implode("\n", $topic->keywords ?? []) }}</textarea></label>
                    <label>Content<textarea name="content" rows="4" required>{{ $topic->content }}</textarea></label>
                    <label class="assistant-check-row"><input type="checkbox" name="active" value="1" @checked($topic->active)> Aktif</label>
                    <button class="assistant-admin-btn primary" type="submit">Update</button>
                </form>
                <form method="POST" action="{{ route('assistant.topics.destroy', $topic, false) }}" class="assistant-delete-form js-confirm-form" data-confirm-message="Hapus topic assistant ini?">
                    @csrf @method('DELETE')
                    <button type="submit" class="assistant-admin-btn danger">Hapus Topic</button>
                </form>
            </details>
        @empty
            <p class="assistant-empty">Belum ada topic.</p>
        @endforelse
    </section>

    <section class="assistant-training-list">
        <h3>Rules</h3>
        @forelse($rules as $rule)
            <details class="assistant-training-item">
                <summary><span>{{ $rule->title }}</span><em>{{ $rule->condition_type }} / {{ $rule->severity }}</em></summary>
                <form method="POST" action="{{ route('assistant.rules.update', $rule, false) }}" class="assistant-training-form compact">
                    @csrf @method('PUT')
                    <div class="assistant-form-row"><label>Kode<input name="code" value="{{ $rule->code }}" required></label><label>Severity<input name="severity" value="{{ $rule->severity }}" required></label></div>
                    <label>Judul<input name="title" value="{{ $rule->title }}" required></label>
                    <label>Kondisi<select name="condition_type">@foreach($conditionTypes as $key => $label)<option value="{{ $key }}" @selected($rule->condition_type === $key)>{{ $label }}</option>@endforeach</select></label>
                    <label>Payload JSON<textarea name="condition_payload_text" rows="3">{{ $rule->condition_payload ? json_encode($rule->condition_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea></label>
                    <label>Pesan<textarea name="message" rows="4" required>{{ $rule->message }}</textarea></label>
                    <div class="assistant-form-row"><label>Action Label<input name="action_label" value="{{ $rule->action_label }}"></label><label>Action URL<input name="action_url" value="{{ $rule->action_url }}"></label></div>
                    <label class="assistant-check-row"><input type="checkbox" name="active" value="1" @checked($rule->active)> Aktif</label>
                    <button class="assistant-admin-btn primary" type="submit">Update</button>
                </form>
                <form method="POST" action="{{ route('assistant.rules.destroy', $rule, false) }}" class="assistant-delete-form js-confirm-form" data-confirm-message="Hapus rule assistant ini?">
                    @csrf @method('DELETE')
                    <button type="submit" class="assistant-admin-btn danger">Hapus Rule</button>
                </form>
            </details>
        @empty
            <p class="assistant-empty">Belum ada rule.</p>
        @endforelse
    </section>

    <section class="assistant-training-list">
        <h3>File Templates</h3>
        @forelse($templates as $template)
            <details class="assistant-training-item">
                <summary><span>{{ $template->name }}</span><em>{{ $template->type }}</em></summary>
                <form method="POST" action="{{ route('assistant.templates.update', $template, false) }}" class="assistant-training-form compact">
                    @csrf @method('PUT')
                    <div class="assistant-form-row"><label>Tipe<select name="type"><option value="excel" @selected($template->type === 'excel')>Excel</option><option value="pdf" @selected($template->type === 'pdf')>PDF</option></select></label><label>Nama<input name="name" value="{{ $template->name }}" required></label></div>
                    <label>Kolom Wajib<textarea name="required_columns_text" rows="3">{{ implode("\n", $template->required_columns ?? []) }}</textarea></label>
                    <label>Kolom Opsional<textarea name="optional_columns_text" rows="3">{{ implode("\n", $template->optional_columns ?? []) }}</textarea></label>
                    <label>Rules JSON<textarea name="validation_rules_text" rows="3">{{ $template->validation_rules ? json_encode($template->validation_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea></label>
                    <label class="assistant-check-row"><input type="checkbox" name="active" value="1" @checked($template->active)> Aktif</label>
                    <button class="assistant-admin-btn primary" type="submit">Update</button>
                </form>
                <form method="POST" action="{{ route('assistant.templates.destroy', $template, false) }}" class="assistant-delete-form js-confirm-form" data-confirm-message="Hapus template file ini?">
                    @csrf @method('DELETE')
                    <button type="submit" class="assistant-admin-btn danger">Hapus Template</button>
                </form>
            </details>
        @empty
            <p class="assistant-empty">Belum ada template.</p>
        @endforelse
    </section>
</div>
@endsection
