@php
    use App\Models\RolePermission;
    $productivityRole = (string) (auth()->user()?->role ?? 'viewer');
    $canInputData = RolePermission::hasAccess($productivityRole, 'input_data');
    $canDatabase = RolePermission::hasAccess($productivityRole, 'database');
    $productivityActions = collect([
        ['label' => 'Open My Tasks', 'hint' => 'Role-based assignments', 'url' => route('my-tasks', absolute: false), 'key' => 'G T'],
        ['label' => 'Notification Center', 'hint' => 'Baca dan atur notifikasi', 'url' => route('notifications.index', absolute: false), 'key' => ''],
        ['label' => 'Buka Project', 'hint' => 'Cari dan pantau workflow', 'url' => route('project', absolute: false), 'key' => 'G P'],
        ['label' => 'Dashboard', 'hint' => 'Ringkasan seluruh project', 'url' => route('dashboard', absolute: false), 'key' => 'G D'],
        $canInputData ? ['label' => 'Buat Project Baru', 'hint' => 'Mulai penerimaan dokumen', 'url' => route('tracking-documents.create', absolute: false), 'key' => ''] : null,
        $canInputData ? ['label' => 'Form Costing', 'hint' => 'Input komponen biaya', 'url' => route('form', absolute: false), 'key' => ''] : null,
        $canDatabase ? ['label' => 'Database Part', 'hint' => 'Cari master material', 'url' => route('database.parts', absolute: false), 'key' => ''] : null,
        ['label' => 'Help Center', 'hint' => 'Guides and shortcuts', 'url' => route('help-center', absolute: false), 'key' => '?'],
    ])->filter()->values();
@endphp

<div class="productivity-overlay" id="productivityOverlay" hidden>
    <section class="productivity-dialog" role="dialog" aria-modal="true" aria-labelledby="productivityTitle">
        <div class="productivity-search-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="productivitySearchInput" type="search" autocomplete="off" placeholder="Cari project, part number, customer, atau material..." aria-label="Pencarian global">
            <button type="button" id="productivityClose" aria-label="Tutup pencarian">ESC</button>
        </div>

        <div class="productivity-body">
            <div id="productivitySearchState" class="productivity-search-state" hidden></div>
            <div id="productivitySearchResults" class="productivity-results" hidden></div>
            <div id="productivityHome">
                <div class="productivity-section-head"><h3 id="productivityTitle">Aksi Cepat</h3><button type="button" id="favoriteCurrentPage">☆ Favoritkan halaman ini</button></div>
                <div class="productivity-action-grid">
                    @foreach($productivityActions as $action)
                        <a href="{{ $action['url'] }}" class="productivity-action">
                            <span><b>{{ $action['label'] }}</b><small>{{ $action['hint'] }}</small></span>
                            @if($action['key'])<kbd>{{ $action['key'] }}</kbd>@else<span class="productivity-arrow">→</span>@endif
                        </a>
                    @endforeach
                </div>
                <div class="productivity-history-grid">
                    <div><h3>Terakhir Dibuka</h3><div id="productivityRecent" class="productivity-link-list"></div></div>
                    <div><h3>Favorit</h3><div id="productivityFavorites" class="productivity-link-list"></div></div>
                </div>
            </div>
        </div>
        <footer class="productivity-footer"><span><kbd>↑</kbd><kbd>↓</kbd> memilih</span><span><kbd>Enter</kbd> membuka</span><span><kbd>Ctrl</kbd><kbd>K</kbd> cari dari mana saja</span></footer>
    </section>
</div>

<style>
    .productivity-search-launch kbd{margin-left:3px;padding:2px 5px;border:1px solid rgba(255,255,255,.35);border-radius:5px;background:rgba(255,255,255,.1);color:inherit;font:700 9px/1.2 inherit}.productivity-overlay{position:fixed;z-index:15000;inset:0;padding:9vh 18px;background:rgba(8,25,49,.57);backdrop-filter:blur(5px)}.productivity-overlay[hidden]{display:none}.productivity-dialog{width:min(720px,100%);max-height:80vh;margin:0 auto;overflow:hidden;border:1px solid #cbd9e8;border-radius:16px;background:#f8fafc;box-shadow:0 28px 70px rgba(3,22,48,.35)}.productivity-search-row{display:flex;align-items:center;gap:11px;padding:14px 16px;border-bottom:1px solid #dce5ef;background:#fff}.productivity-search-row>svg{width:22px;color:#1670da}.productivity-search-row input{min-width:0;flex:1;border:0;outline:0;color:#142b49;font-size:15px}.productivity-search-row button{padding:5px 7px;border:1px solid #cfdae6;border-radius:6px;background:#f5f8fb;color:#687a91;font-size:10px;font-weight:800;cursor:pointer}.productivity-body{max-height:calc(80vh - 108px);padding:15px;overflow:auto}.productivity-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.productivity-section-head h3,.productivity-history-grid h3{margin:0 0 9px;color:#52667e;font-size:11px;letter-spacing:.08em;text-transform:uppercase}.productivity-section-head button{margin-bottom:9px;border:0;background:transparent;color:#1468cb;font-size:11px;font-weight:800;cursor:pointer}.productivity-action-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.productivity-action,.productivity-result,.productivity-stored-link{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border:1px solid #dbe4ee;border-radius:10px;background:#fff;color:#1d3655;text-decoration:none}.productivity-action:hover,.productivity-result:hover,.productivity-result.is-active,.productivity-stored-link:hover{border-color:#6daaf0;background:#eef6ff}.productivity-action b,.productivity-action small{display:block}.productivity-action b{font-size:12px}.productivity-action small{margin-top:2px;color:#74869a;font-size:10px}.productivity-action kbd,.productivity-footer kbd{padding:2px 5px;border:1px solid #d4deea;border-bottom-width:2px;border-radius:5px;background:#f8fafc;color:#687b91;font:700 9px/1.2 inherit}.productivity-arrow{color:#1670da;font-weight:900}.productivity-history-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:17px}.productivity-link-list{display:grid;gap:6px}.productivity-stored-link{padding:8px 10px;font-size:11px}.productivity-stored-link span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.productivity-stored-link button{border:0;background:transparent;color:#94a3b8;cursor:pointer}.productivity-empty{padding:13px;border:1px dashed #cad7e4;border-radius:9px;color:#8494a7;font-size:11px;text-align:center}.productivity-results{display:grid;gap:7px}.productivity-result{width:100%;cursor:pointer;text-align:left}.productivity-result>span{min-width:0}.productivity-result b,.productivity-result small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.productivity-result b{font-size:12px}.productivity-result small{margin-top:3px;color:#73859a;font-size:10px}.productivity-result em{flex:0 0 auto;padding:3px 7px;border-radius:10px;background:#eaf3ff;color:#1263bd;font-size:9px;font-style:normal;font-weight:800}.productivity-search-state{padding:38px 15px;color:#718399;font-size:13px;text-align:center}.productivity-footer{display:flex;gap:16px;padding:9px 15px;border-top:1px solid #dce5ef;background:#fff;color:#7a8b9e;font-size:10px}.productivity-footer span{display:flex;align-items:center;gap:3px}@media(max-width:620px){.productivity-search-launch span,.productivity-search-launch kbd{display:none}.productivity-overlay{padding:4vh 10px}.productivity-action-grid,.productivity-history-grid{grid-template-columns:1fr}.productivity-footer span:last-child{display:none}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('productivityOverlay');
    const input = document.getElementById('productivitySearchInput');
    const home = document.getElementById('productivityHome');
    const results = document.getElementById('productivitySearchResults');
    const state = document.getElementById('productivitySearchState');
    if (!overlay || !input) return;

    const searchUrl = @json(route('global-search', absolute: false));
    const helpUrl = @json(route('help-center', absolute: false));
    const current = { title: document.querySelector('.header-title')?.textContent?.trim() || document.title, url: location.pathname + location.search };
    const storage = {
        read(key) { try { const value = JSON.parse(localStorage.getItem(key) || '[]'); return Array.isArray(value) ? value : []; } catch (_) { return []; } },
        write(key, value) { try { localStorage.setItem(key, JSON.stringify(value.slice(0, 8))); } catch (_) {} }
    };
    let activeIndex = -1;
    let timer = null;
    let request = null;
    let sequence = '';

    const rememberPage = () => {
        if (location.pathname === '/global-search') return;
        const pages = storage.read('costing_recent_pages').filter((item) => item.url !== current.url);
        storage.write('costing_recent_pages', [current, ...pages]);
    };
    const renderStored = (id, key, removable) => {
        const container = document.getElementById(id);
        const items = storage.read(key);
        container.replaceChildren();
        if (!items.length) { const empty = document.createElement('div'); empty.className = 'productivity-empty'; empty.textContent = removable ? 'Belum ada halaman favorit.' : 'Riwayat halaman masih kosong.'; container.append(empty); return; }
        items.slice(0, 5).forEach((item) => {
            const link = document.createElement('a'); link.className = 'productivity-stored-link'; link.href = item.url;
            const label = document.createElement('span'); label.textContent = item.title; link.append(label);
            if (removable) { const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.title = 'Hapus favorit'; remove.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); storage.write(key, storage.read(key).filter((saved) => saved.url !== item.url)); renderStored(id, key, true); updateFavoriteButton(); }); link.append(remove); }
            container.append(link);
        });
    };
    const updateFavoriteButton = () => {
        const button = document.getElementById('favoriteCurrentPage');
        const saved = storage.read('costing_favorite_pages').some((item) => item.url === current.url);
        button.textContent = saved ? '★ Hapus dari favorit' : '☆ Favoritkan halaman ini';
    };
    const renderHome = () => { home.hidden = false; results.hidden = true; state.hidden = true; renderStored('productivityRecent', 'costing_recent_pages', false); renderStored('productivityFavorites', 'costing_favorite_pages', true); updateFavoriteButton(); activeIndex = -1; };
    const open = () => { overlay.hidden = false; document.body.style.overflow = 'hidden'; renderHome(); window.setTimeout(() => input.focus(), 30); };
    const close = () => { overlay.hidden = true; document.body.style.overflow = ''; input.value = ''; request?.abort(); renderHome(); };
    const selectable = () => Array.from(results.querySelectorAll('.productivity-result'));
    const setActive = (index) => { const rows = selectable(); if (!rows.length) return; activeIndex = (index + rows.length) % rows.length; rows.forEach((row, i) => row.classList.toggle('is-active', i === activeIndex)); rows[activeIndex].scrollIntoView({ block: 'nearest' }); };
    const showState = (message) => { home.hidden = true; results.hidden = true; state.hidden = false; state.textContent = message; };
    const renderResults = (items) => {
        home.hidden = true; state.hidden = true; results.hidden = false; results.replaceChildren(); activeIndex = -1;
        if (!items.length) { showState('Tidak ada hasil. Coba part number, customer, model, atau nama material lain.'); return; }
        items.forEach((item) => {
            const row = document.createElement('button'); row.type = 'button'; row.className = 'productivity-result';
            const text = document.createElement('span'); const title = document.createElement('b'); const description = document.createElement('small'); const type = document.createElement('em');
            title.textContent = item.title; description.textContent = item.description || ''; type.textContent = item.type; text.append(title, description); row.append(text, type);
            row.addEventListener('click', () => { location.href = item.url; }); results.append(row);
        });
    };
    const search = async (query) => {
        request?.abort(); request = new AbortController(); showState('Mencari...');
        try { const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' }, signal: request.signal }); if (!response.ok) throw new Error(); renderResults((await response.json()).results || []); }
        catch (error) { if (error.name !== 'AbortError') showState('Pencarian gagal dimuat. Silakan coba lagi.'); }
    };

    rememberPage();
    document.querySelectorAll('[data-productivity-open]').forEach((button) => button.addEventListener('click', open));
    document.getElementById('productivityClose').addEventListener('click', close);
    overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
    document.getElementById('favoriteCurrentPage').addEventListener('click', () => { const items = storage.read('costing_favorite_pages'); const exists = items.some((item) => item.url === current.url); storage.write('costing_favorite_pages', exists ? items.filter((item) => item.url !== current.url) : [current, ...items]); renderStored('productivityFavorites', 'costing_favorite_pages', true); updateFavoriteButton(); });
    input.addEventListener('input', () => { window.clearTimeout(timer); const query = input.value.trim(); if (query.length < 2) { renderHome(); return; } timer = window.setTimeout(() => search(query), 260); });
    input.addEventListener('keydown', (event) => { if (event.key === 'ArrowDown') { event.preventDefault(); setActive(activeIndex + 1); } else if (event.key === 'ArrowUp') { event.preventDefault(); setActive(activeIndex - 1); } else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); selectable()[activeIndex]?.click(); } });
    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); overlay.hidden ? open() : close(); return; }
        if (!overlay.hidden && event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (typing || !overlay.hidden || event.ctrlKey || event.altKey || event.metaKey) return;
        if (event.key === '?') { event.preventDefault(); location.href = helpUrl; return; }
        const key = event.key.toLowerCase();
        if (key === 'g') { sequence = 'g'; window.setTimeout(() => { sequence = ''; }, 900); return; }
        if (sequence === 'g') { const targets = { d: @json(route('dashboard', absolute: false)), p: @json(route('project', absolute: false)), t: @json(route('my-tasks', absolute: false)) }; if (targets[key]) { event.preventDefault(); location.href = targets[key]; } sequence = ''; }
    });
});
</script>
