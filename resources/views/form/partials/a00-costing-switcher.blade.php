@if (($a00CostingTabs ?? collect())->count() > 1)
<style>
    .a00-costing-switcher { display:flex; align-items:center; box-sizing:border-box; width:100%; gap:18px; margin:0 0 12px; padding:10px 12px; background:linear-gradient(90deg,#f8fbff,#fff); border:1px solid #d7e3f2; border-radius:11px; box-shadow:0 3px 12px rgba(15,23,42,.035); }
    .a00-costing-switcher-head { display:grid; flex:0 0 175px; gap:2px; padding-left:3px; }
    .a00-costing-switcher-head strong { color:#18345d; font-size:11px; }
    .a00-costing-switcher-head span { color:#7b8ca5; font-size:9px; }
    .a00-costing-tabs { display:flex; flex:1; gap:7px; min-width:0; overflow-x:auto; padding:1px 0 2px; }
    .a00-costing-tab { display:flex; flex:0 0 170px; flex-direction:column; justify-content:center; min-height:44px; padding:7px 10px; border:1px solid #bfd5f3; border-radius:8px; color:#1e40af; text-decoration:none; background:#eff6ff; transition:.15s ease; }
    .a00-costing-tab:hover { border-color:#60a5fa; transform:translateY(-1px); }
    .a00-costing-tab strong { overflow:hidden; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
    .a00-costing-tab small { overflow:hidden; color:#64748b; font-size:8px; text-overflow:ellipsis; white-space:nowrap; }
    .a00-costing-tab.active { background:#2563eb; border-color:#2563eb; color:#fff; }
    .a00-costing-tab.active small { color:#dbeafe; }
    @media (max-width:760px) { .a00-costing-switcher { align-items:stretch; flex-direction:column; gap:8px; } .a00-costing-switcher-head { flex-basis:auto; } }
</style>
<div class="a00-costing-switcher">
    <div class="a00-costing-switcher-head">
        <strong>Form Costing A00 Gabung</strong>
        <span>{{ $a00CostingTabs->count() }} assy dalam satu dokumen A00</span>
    </div>
    <nav class="a00-costing-tabs" aria-label="Form Costing per Assy">
        @foreach ($a00CostingTabs as $tab)
            <a class="a00-costing-tab {{ (int) $trackingRevisionId === (int) $tab->revision->id ? 'active' : '' }}" href="{{ route('form', ['tracking_revision_id' => $tab->revision->id], false) }}">
                <strong>{{ $tab->assy_number }}</strong>
                <small>{{ $tab->assy_name }}</small>
            </a>
        @endforeach
    </nav>
</div>
@endif
