@if ($paginator->hasPages())
@php
    $makeRelative = function($url) {
        if (!$url) return '#';
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        return $path . $query;
    };
@endphp
<div style="display:flex; align-items:center; gap:0.25rem; list-style:none; margin:0; padding:0; flex-wrap:wrap;">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#cbd5e1;background:#fff;cursor:default;">&lsaquo;</span>
    @else
        <a href="{{ $makeRelative($paginator->previousPageUrl()) }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#475569;background:#fff;text-decoration:none;" onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb';this.style.borderColor='#93c5fd'" onmouseout="this.style.background='#fff';this.style.color='#475569';this.style.borderColor='#e2e8f0'">&lsaquo;</a>
    @endif

    {{-- Page numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#94a3b8;background:#fff;">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #2563eb;font-size:0.82rem;font-weight:600;color:#fff;background:#2563eb;">{{ $page }}</span>
                @else
                    <a href="{{ $makeRelative($url) }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#475569;background:#fff;text-decoration:none;" onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb';this.style.borderColor='#93c5fd'" onmouseout="this.style.background='#fff';this.style.color='#475569';this.style.borderColor='#e2e8f0'">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $makeRelative($paginator->nextPageUrl()) }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#475569;background:#fff;text-decoration:none;" onmouseover="this.style.background='#eff6ff';this.style.color='#2563eb';this.style.borderColor='#93c5fd'" onmouseout="this.style.background='#fff';this.style.color='#475569';this.style.borderColor='#e2e8f0'">&rsaquo;</a>
    @else
        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.5rem;border-radius:6px;border:1px solid #e2e8f0;font-size:0.82rem;font-weight:600;color:#cbd5e1;background:#fff;cursor:default;">&rsaquo;</span>
    @endif
</div>
@endif
