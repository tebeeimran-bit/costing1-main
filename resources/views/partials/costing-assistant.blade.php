@auth
@if(in_array(auth()->user()->role, ['admin', 'admin_costing'], true))
<div class="costing-assistant" id="costingAssistant" data-route="{{ request()->route()?->getName() }}" data-path="/{{ request()->path() }}">
    <button type="button" class="costing-assistant-toggle" id="costingAssistantToggle" aria-label="Buka Costing Assistant" title="Costing Assistant">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>
    </button>

    <aside class="costing-assistant-panel" id="costingAssistantPanel" aria-label="Costing Assistant" aria-hidden="true">
        <header class="costing-assistant-header">
            <div>
                <h2>Costing Assistant</h2>
                <p>Rule-based, lokal, tanpa AI cloud.</p>
            </div>
            <button type="button" class="costing-assistant-close" id="costingAssistantClose" aria-label="Tutup">×</button>
        </header>

        <div class="costing-assistant-tabs" role="tablist">
            <button type="button" class="active" data-assistant-tab="chat">Guide</button>
            <button type="button" data-assistant-tab="file">File Check</button>
        </div>

        <section class="costing-assistant-body active" data-assistant-pane="chat">
            <div class="costing-assistant-snapshot" id="costingAssistantSnapshot">
                <span>Memuat konteks...</span>
            </div>
            <div class="costing-assistant-messages" id="costingAssistantMessages" aria-live="polite"></div>
            <div class="costing-assistant-prompts" id="costingAssistantPrompts"></div>
            <form class="costing-assistant-chat-form" id="costingAssistantChatForm" data-skip-loading-overlay="true">
                <input type="text" id="costingAssistantInput" maxlength="500" placeholder="Tanya: kenapa belum bisa submit?" autocomplete="off">
                <button type="submit" aria-label="Kirim">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </form>
        </section>

        <section class="costing-assistant-body" data-assistant-pane="file">
            <form class="costing-assistant-file-form" id="costingAssistantFileForm" enctype="multipart/form-data" data-skip-loading-overlay="true">
                <label>Template
                    <select id="costingAssistantTemplate" name="template_id"></select>
                </label>
                <label>File Excel/PDF
                    <input type="file" name="assistant_file" accept=".xlsx,.xls,.csv,.pdf" required>
                </label>
                <button type="submit" class="assistant-file-submit">Cek File</button>
            </form>
            <div class="costing-assistant-workflow-actions" id="costingAssistantWorkflowActions" hidden>
                <button type="button" class="assistant-file-secondary" id="costingAssistantPreviewProject">Preview New Project</button>
                <button type="button" class="assistant-file-submit" id="costingAssistantCreateProject" hidden>Buat New Project</button>
            </div>
            <div class="costing-assistant-file-result" id="costingAssistantFileResult"></div>
        </section>
    </aside>
</div>

<script>
(function () {
    const root = document.getElementById('costingAssistant');
    if (!root) return;

    const toggle = document.getElementById('costingAssistantToggle');
    const panel = document.getElementById('costingAssistantPanel');
    const close = document.getElementById('costingAssistantClose');
    const messages = document.getElementById('costingAssistantMessages');
    const prompts = document.getElementById('costingAssistantPrompts');
    const snapshot = document.getElementById('costingAssistantSnapshot');
    const chatForm = document.getElementById('costingAssistantChatForm');
    const chatInput = document.getElementById('costingAssistantInput');
    const fileForm = document.getElementById('costingAssistantFileForm');
    const fileResult = document.getElementById('costingAssistantFileResult');
    const workflowActions = document.getElementById('costingAssistantWorkflowActions');
    const previewProjectButton = document.getElementById('costingAssistantPreviewProject');
    const createProjectButton = document.getElementById('costingAssistantCreateProject');
    const templateSelect = document.getElementById('costingAssistantTemplate');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let booted = false;

    function payload(extra) {
        return Object.assign({ route: root.dataset.route || '', path: root.dataset.path || window.location.pathname }, extra || {});
    }

    function openPanel() {
        panel.classList.add('open');
        panel.setAttribute('aria-hidden', 'false');
        if (!booted) bootAssistant();
    }

    function closePanel() {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
    }

    async function postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(data || {})
        });
        if (!response.ok) throw new Error('Request gagal: ' + response.status);
        return response.json();
    }

    async function bootAssistant() {
        booted = true;
        addMessage('assistant', 'Saya membaca konteks halaman dan rule aktif...');
        try {
            const data = await postJson('{{ route('assistant.bootstrap', absolute: false) }}', payload());
            renderSnapshot(data.snapshot);
            renderPrompts(data.quick_prompts || []);
            renderTemplates(data.templates || []);
            messages.innerHTML = '';
            addMessage('assistant', greeting(data));
            renderRuleCards(data.rules || []);
        } catch (error) {
            addMessage('assistant', 'Costing Assistant belum bisa dimuat. Coba refresh halaman.');
        }
    }

    function greeting(data) {
        const s = data.snapshot || {};
        return 'Konteks aktif: ' + (s.module || 'general') + '. Saya akan menjawab dari rule, FAQ, dan data lokal aplikasi.';
    }

    function renderSnapshot(s) {
        if (!s) return;
        snapshot.innerHTML = '<span>Module: <strong>' + escapeHtml(s.module) + '</strong></span>'
            + '<span>Unpriced: <strong>' + s.unresolved_unpriced_count + '</strong></span>'
            + '<span>Waiting approval: <strong>' + s.waiting_approval_count + '</strong></span>'
            + '<span>Rate ' + escapeHtml(s.current_period) + ': <strong>' + (s.current_month_rate_exists ? 'OK' : 'Belum') + '</strong></span>';
    }

    function renderPrompts(items) {
        prompts.innerHTML = '';
        items.forEach(function (text) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = text;
            button.addEventListener('click', function () { sendMessage(text); });
            prompts.appendChild(button);
        });
    }

    function renderTemplates(items) {
        templateSelect.innerHTML = '<option value="">Tanpa template khusus</option>';
        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name + ' (' + item.type + ')';
            templateSelect.appendChild(option);
        });
    }

    function addMessage(type, text) {
        const item = document.createElement('div');
        item.className = 'assistant-message ' + type;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }

    function renderRuleCards(rules) {
        if (!rules.length) return;
        const wrap = document.createElement('div');
        wrap.className = 'assistant-rule-list';
        rules.slice(0, 4).forEach(function (rule) {
            const card = document.createElement('div');
            card.className = 'assistant-rule-card ' + rule.severity;
            card.innerHTML = '<strong>' + escapeHtml(rule.title) + '</strong><p>' + escapeHtml(rule.message) + '</p>';
            if (rule.action_url && rule.action_label) {
                const link = document.createElement('a');
                link.href = rule.action_url;
                link.textContent = rule.action_label;
                card.appendChild(link);
            }
            wrap.appendChild(card);
        });
        messages.appendChild(wrap);
    }

    async function sendMessage(text) {
        const message = (text || chatInput.value || '').trim();
        if (!message) return;
        chatInput.value = '';
        addMessage('user', message);
        try {
            const data = await postJson('{{ route('assistant.chat', absolute: false) }}', payload({ message: message }));
            addMessage('assistant', data.reply || 'Tidak ada jawaban.');
            renderRuleCards(data.rules || []);
        } catch (error) {
            addMessage('assistant', 'Gagal membaca rule assistant. Coba lagi sebentar.');
        }
    }


    async function postFile(url) {
        const formData = new FormData(fileForm);
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: formData
        });
        if (!response.ok) throw new Error('Request gagal: ' + response.status);
        return response.json();
    }

    function selectedFileIsSpreadsheet() {
        const file = fileForm.querySelector('input[type="file"]')?.files?.[0];
        if (!file) return false;
        return /\.(xlsx|xls|csv)$/i.test(file.name || '');
    }

    function renderProjectPreview(data) {
        const project = data.project || {};
        const summary = data.summary || {};
        const issues = (data.issues || []).map(function (issue) { return '<li>' + escapeHtml(issue) + '</li>'; }).join('');
        fileResult.innerHTML = '<div class="assistant-file-card ' + escapeHtml(data.status || 'info') + '">'
            + '<h3>Preview New Project</h3>'
            + '<p>' + escapeHtml(data.message || '-') + '</p>'
            + '<dl>'
            + '<dt>Customer</dt><dd>' + escapeHtml(project.customer || '-') + '</dd>'
            + '<dt>Model</dt><dd>' + escapeHtml(project.model || '-') + '</dd>'
            + '<dt>Category</dt><dd>' + escapeHtml(project.business_category || 'WIRING HARNESS') + '</dd>'
            + '<dt>Part No</dt><dd>' + escapeHtml(project.part_no || '-') + '</dd>'
            + '<dt>Part Name</dt><dd>' + escapeHtml(project.part_name || '-') + '</dd>'
            + '<dt>Rows</dt><dd>' + escapeHtml(String(summary.total_rows || 0)) + '</dd>'
            + '</dl>'
            + (issues ? '<ul>' + issues + '</ul>' : '')
            + '</div>';
        createProjectButton.hidden = data.status !== 'success';
    }

    function renderProjectCreated(data) {
        const project = data.project || {};
        const revision = data.revision || {};
        fileResult.innerHTML = '<div class="assistant-file-card success">'
            + '<h3>New Project berhasil dibuat</h3>'
            + '<p>' + escapeHtml(data.message || '-') + '</p>'
            + '<dl>'
            + '<dt>Customer</dt><dd>' + escapeHtml(project.customer || '-') + '</dd>'
            + '<dt>Model</dt><dd>' + escapeHtml(project.model || '-') + '</dd>'
            + '<dt>Part No</dt><dd>' + escapeHtml(project.part_number || '-') + '</dd>'
            + '<dt>Revision</dt><dd>' + escapeHtml(revision.version_label || '-') + '</dd>'
            + '<dt>Status</dt><dd>' + escapeHtml(revision.status || '-') + '</dd>'
            + '</dl>'
            + (data.redirect_url ? '<a class="assistant-created-link" href="' + data.redirect_url + '">Buka Project</a>' : '')
            + '</div>';
        createProjectButton.hidden = true;
    }

    function renderFileResult(data) {
        const issues = (data.issues || []).map(function (issue) { return '<li>' + escapeHtml(issue) + '</li>'; }).join('');
        
        let extractedInfoHtml = '';
        if (data.extracted_info && Object.keys(data.extracted_info).length > 0) {
            extractedInfoHtml = '<dl>';
            for (const key in data.extracted_info) {
                extractedInfoHtml += '<dt>' + escapeHtml(key) + '</dt><dd>' + escapeHtml(data.extracted_info[key]) + '</dd>';
            }
            extractedInfoHtml += '</dl>';
        }

        fileResult.innerHTML = '<div class="assistant-file-card ' + escapeHtml(data.status || 'info') + '">'
            + '<h3>' + escapeHtml(data.file_name || 'File') + '</h3>'
            + '<p>' + escapeHtml(data.message || '-') + '</p>'
            + '<dl><dt>Ukuran</dt><dd>' + escapeHtml(String(data.size_kb || '-')) + ' KB</dd><dt>Rows</dt><dd>' + escapeHtml(String(data.total_rows ?? '-')) + '</dd><dt>Sheet</dt><dd>' + escapeHtml(data.sheet_name || '-') + '</dd></dl>'
            + extractedInfoHtml
            + (issues ? '<ul>' + issues + '</ul>' : '')
            + '</div>';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"]/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char];
        });
    }

    toggle.addEventListener('click', openPanel);
    close.addEventListener('click', closePanel);
    chatForm.addEventListener('submit', function (event) { event.preventDefault(); sendMessage(); });

    document.querySelectorAll('[data-assistant-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-assistant-tab]').forEach(function (tab) { tab.classList.remove('active'); });
            document.querySelectorAll('[data-assistant-pane]').forEach(function (pane) { pane.classList.remove('active'); });
            button.classList.add('active');
            document.querySelector('[data-assistant-pane="' + button.dataset.assistantTab + '"]').classList.add('active');
        });
    });

    fileForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        fileResult.innerHTML = '<div class="assistant-file-card info"><p>Mengecek file secara lokal...</p></div>';
        workflowActions.hidden = true;
        createProjectButton.hidden = true;
        try {
            renderFileResult(await postFile('{{ route('assistant.inspect-file', absolute: false) }}'));
            workflowActions.hidden = !selectedFileIsSpreadsheet();
        } catch (error) {
            fileResult.innerHTML = '<div class="assistant-file-card danger"><p>File belum bisa dicek. Pastikan format dan ukuran sesuai.</p></div>';
        }
    });

    previewProjectButton.addEventListener('click', async function () {
        fileResult.innerHTML = '<div class="assistant-file-card info"><p>Membaca project dari partlist...</p></div>';
        createProjectButton.hidden = true;
        try {
            renderProjectPreview(await postFile('{{ route('assistant.partlist-project.preview', absolute: false) }}'));
        } catch (error) {
            fileResult.innerHTML = '<div class="assistant-file-card danger"><p>Partlist belum bisa dipreview sebagai New Project. Pastikan header customer, model, part_no, dan part_name tersedia.</p></div>';
        }
    });

    createProjectButton.addEventListener('click', function () {
        if (typeof window.openAppConfirm === 'function') {
            window.openAppConfirm('Buat New Project dari partlist ini?', proceedCreateProject);
        } else if (window.confirm('Buat New Project dari partlist ini?')) {
            proceedCreateProject();
        }
    });

    async function proceedCreateProject() {
        fileResult.innerHTML = '<div class="assistant-file-card info"><p>Membuat New Project...</p></div>';
        try {
            const data = await postFile('{{ route('assistant.partlist-project.create', absolute: false) }}');
            if (data.status === 'success') {
                renderProjectCreated(data);
                return;
            }
            renderProjectPreview(data.preview || data);
        } catch (error) {
            fileResult.innerHTML = '<div class="assistant-file-card danger"><p>New Project belum bisa dibuat. Cek ulang data wajib dan coba lagi.</p></div>';
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closePanel();
    });
})();
</script>
@endif
@endauth
