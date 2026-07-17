@php
    $notificationItems = app(\App\Services\Notification\ProjectNotificationService::class)->forUser(auth()->user());
    $notificationCount = $notificationItems->where('is_read', false)->count();
@endphp
<div class="notify-v2" id="notifyV2">
    <button type="button" class="notify-v2-button" id="notifyV2Button" aria-label="Buka notifikasi">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        @if($notificationCount)<span>{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>@endif
    </button>
    <div class="notify-v2-dropdown" id="notifyV2Dropdown">
        <header><div><b>Notifikasi</b><small>{{ $notificationCount }} belum dibaca</small></div><a href="{{ route('notifications.index', absolute: false) }}">Lihat Semua</a></header>
        <div class="notify-v2-list">
            @forelse($notificationItems->take(6) as $item)
                <article class="notify-v2-item color-{{ $item['color'] }} {{ $item['is_read'] ? 'is-read' : '' }}">
                    <i>{{ $item['type'] === 'pricing' ? 'Rp' : ($item['type'] === 'mention' ? '@' : '!') }}</i>
                    <div><b>{{ $item['title'] }}</b><span>{{ $item['line'] }}</span><p>{{ $item['description'] }}</p><button type="button" class="notify-v2-open" data-key="{{ $item['key'] }}" data-url="{{ $item['url'] }}">{{ $item['button_label'] }}</button></div>
                </article>
            @empty<div class="notify-v2-empty">Tidak ada notifikasi aktif.</div>@endforelse
        </div>
        <footer><a href="{{ route('notifications.index', absolute: false) }}">Kelola notifikasi &amp; preferensi</a></footer>
    </div>
</div>
<style>
    .notify-v2{position:relative;z-index:2000;margin-left:.65rem}.notify-v2-button{position:relative;display:grid;place-items:center;width:42px;height:42px;border:1px solid rgba(255,255,255,.25);border-radius:13px;background:rgba(255,255,255,.13);color:#fff;cursor:pointer}.notify-v2-button svg{width:20px}.notify-v2-button>span{position:absolute;top:-6px;right:-6px;min-width:19px;height:19px;padding:0 4px;border:2px solid #1751b7;border-radius:20px;background:#ef4444;color:#fff;font-size:9px;font-weight:900;line-height:15px}.notify-v2-dropdown{display:none;position:absolute;top:52px;right:0;width:min(430px,calc(100vw - 25px));overflow:hidden;border:1px solid #d8e3ef;border-radius:15px;background:#fff;box-shadow:0 22px 55px rgba(10,31,57,.3)}.notify-v2-dropdown.open{display:block}.notify-v2-dropdown header{display:flex;align-items:center;justify-content:space-between;padding:13px 15px;border-bottom:1px solid #e3eaf1}.notify-v2-dropdown header b,.notify-v2-dropdown header small{display:block}.notify-v2-dropdown header b{font-size:13px}.notify-v2-dropdown header small{margin-top:2px;color:#78899d;font-size:9px}.notify-v2-dropdown header a,.notify-v2-dropdown footer a{color:#1168c8;font-size:10px;font-weight:800;text-decoration:none}.notify-v2-list{max-height:390px;padding:8px;overflow:auto}.notify-v2-item{display:grid;grid-template-columns:30px 1fr;gap:8px;padding:10px;margin-bottom:6px;border:1px solid #dbe5ee;border-radius:10px;background:#fff}.notify-v2-item:not(.is-read){border-left:4px solid #1976dc}.notify-v2-item.is-read{opacity:.68;background:#f8fafc}.notify-v2-item>i{display:grid;place-items:center;width:28px;height:28px;border-radius:8px;background:#e5f1ff;color:#1469c5;font-size:9px;font-style:normal;font-weight:900}.notify-v2-item.color-orange>i{background:#ffedd5;color:#c65b0a}.notify-v2-item.color-purple>i{background:#f3e8ff;color:#7e22ce}.notify-v2-item b,.notify-v2-item span{display:block}.notify-v2-item b{font-size:11px}.notify-v2-item span{margin-top:2px;color:#45617d;font-size:9px;font-weight:700}.notify-v2-item p{margin:4px 0;color:#74869a;font-size:9px;line-height:1.4}.notify-v2-open{padding:4px 7px;border:0;border-radius:6px;background:#e9f3ff;color:#1265bd;font-size:9px;font-weight:800;cursor:pointer}.notify-v2-empty{padding:28px;text-align:center;color:#7b8c9e;font-size:11px}.notify-v2-dropdown footer{padding:10px;border-top:1px solid #e3eaf1;background:#f8fafc;text-align:center}@media(max-width:600px){.notify-v2{margin-left:.25rem}.notify-v2-dropdown{right:-8px}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){const root=document.getElementById('notifyV2'),button=document.getElementById('notifyV2Button'),drop=document.getElementById('notifyV2Dropdown');if(!root||!button||!drop)return;button.addEventListener('click',e=>{e.stopPropagation();drop.classList.toggle('open')});document.addEventListener('click',e=>{if(!root.contains(e.target))drop.classList.remove('open')});document.querySelectorAll('.notify-v2-open').forEach(open=>open.addEventListener('click',async()=>{try{await fetch(@json(route('notifications.read', absolute: false)),{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({key:open.dataset.key})})}finally{location.href=open.dataset.url}}));});
</script>
