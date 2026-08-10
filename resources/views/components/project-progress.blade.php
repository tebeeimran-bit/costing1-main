@php($projectProgress=app(\App\Services\ProjectProgressService::class)->compact($revision ?? null))
@once
<style>
.compact-project-progress{position:relative;display:inline-block;min-width:130px}.compact-project-progress>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;color:#1d4ed8;font-size:10px;font-weight:800}.compact-project-progress>summary::-webkit-details-marker{display:none}.compact-progress-count{display:inline-grid;place-items:center;min-width:29px;height:22px;padding:0 5px;border-radius:12px;background:#dbeafe;color:#1d4ed8}.compact-project-progress[open]>summary{color:#1e40af}.compact-project-progress[open]::before{content:"";position:fixed;inset:0;z-index:9998;background:rgba(15,23,42,.28);backdrop-filter:blur(1px)}.compact-progress-popover{position:fixed;z-index:9999;top:50%;left:50%;transform:translate(-50%,-50%);width:min(320px,calc(100vw - 32px));max-height:calc(100vh - 40px);overflow:auto;padding:14px;border:1px solid #bfdbfe;border-radius:12px;background:#fff;box-shadow:0 20px 55px rgba(15,23,42,.3)}.compact-progress-step{display:grid;grid-template-columns:18px 1fr auto;align-items:center;gap:7px;padding:7px 2px;color:#64748b;font-size:10px}.compact-progress-dot{display:grid;place-items:center;width:17px;height:17px;border:1px solid #cbd5e1;border-radius:50%;font-size:8px}.compact-progress-step.done{color:#15803d}.compact-progress-step.done .compact-progress-dot{border-color:#22c55e;background:#22c55e;color:#fff}.compact-progress-step.active{color:#1d4ed8;font-weight:800}.compact-progress-step.active .compact-progress-dot{border-color:#2563eb;background:#2563eb;color:#fff}.compact-progress-state{text-transform:capitalize;font-size:9px}
.compact-progress-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;color:#0f172a;font-size:12px}.compact-progress-close{width:25px;height:25px;border:0;border-radius:7px;background:#eff6ff;color:#1d4ed8;font-size:17px;line-height:1;cursor:pointer}
</style>
@endonce
<details class="compact-project-progress" data-no-row-open>
    <summary><span class="compact-progress-count">{{ $projectProgress['current'] }}/7</span><span>{{ $projectProgress['label'] }}</span></summary>
    <div class="compact-progress-popover">
        <div class="compact-progress-head"><strong>Progress Project</strong><button class="compact-progress-close" type="button" aria-label="Tutup" onclick="this.closest('details').removeAttribute('open')">&times;</button></div>
        @foreach($projectProgress['steps'] as $index=>$step)
            <div class="compact-progress-step {{ $step['state'] }}"><span class="compact-progress-dot">{{ $step['state']==='done'?'✓':$index+1 }}</span><strong>{{ $step['label'] }}</strong><span class="compact-progress-state">{{ $step['state']==='done'?'Selesai':($step['state']==='active'?'Aktif':'Belum') }}</span></div>
        @endforeach
    </div>
</details>
