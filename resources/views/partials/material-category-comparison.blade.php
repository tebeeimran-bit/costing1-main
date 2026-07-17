@php
    $groups = $rows->groupBy(fn($row) => $row['category'] ?? 'Other');
    // Resume COGM is the authoritative total (it may include legacy/manual
    // material adjustments that are not represented by a comparison row).
    $grandA = (float) ($costingA?->material_cost ?? $rows->sum(fn($row) => (float) data_get($row, 'A.total_price_idr', 0)));
    $grandB = (float) ($costingB?->material_cost ?? $rows->sum(fn($row) => (float) data_get($row, 'B.total_price_idr', 0)));
@endphp
<div class="material-category-compare">
    <div class="mc-head"><span>Part Category</span><span>Qty A</span><span>Unit</span><span>Amount A</span><span>% A</span><span>Qty B</span><span>Unit</span><span>Amount B</span><span>% B</span><span>Selisih</span><span></span></div>
    @forelse($groups as $category => $items)
        @php
            $qtyA=$items->sum(fn($r)=>(float)data_get($r,'A.qty_req',0)); $qtyB=$items->sum(fn($r)=>(float)data_get($r,'B.qty_req',0));
            $amountA=$items->sum(fn($r)=>(float)data_get($r,'A.total_price_idr',0)); $amountB=$items->sum(fn($r)=>(float)data_get($r,'B.total_price_idr',0));
            $unitsA=$items->pluck('A.unit')->filter()->unique(); $unitsB=$items->pluck('B.unit')->filter()->unique();
            $amountClassA=$amountA>$amountB?'amount-higher':($amountA<$amountB?'amount-lower':'amount-equal');
            $amountClassB=$amountB>$amountA?'amount-higher':($amountB<$amountA?'amount-lower':'amount-equal');
        @endphp
        <details class="mc-group">
            <summary><strong>{{ $category }}</strong><span>{{ number_format($qtyA,0,',','.') }}</span><span>{{ $unitsA->count()===1?$unitsA->first():'Mixed' }}</span><span class="amount-cell {{ $amountClassA }}">Rp {{ number_format($amountA,0,',','.') }}</span><span>{{ $grandA>0?number_format($amountA/$grandA*100,1,',','.'):'0' }}%</span><span>{{ number_format($qtyB,0,',','.') }}</span><span>{{ $unitsB->count()===1?$unitsB->first():'Mixed' }}</span><span class="amount-cell {{ $amountClassB }}">Rp {{ number_format($amountB,0,',','.') }}</span><span>{{ $grandB>0?number_format($amountB/$grandB*100,1,',','.'):'0' }}%</span><span class="{{ $amountA-$amountB>0?'up':($amountA-$amountB<0?'down':'') }}">{{ $amountA-$amountB>=0?'+':'' }}Rp {{ number_format($amountA-$amountB,0,',','.') }}</span><i>⌄</i></summary>
            <div class="mc-details">
                <div class="mc-detail-head"><span>Part Name / ID Code</span><span>Qty A</span><span>Unit</span><span>Amount A</span><span>% A</span><span>Qty B</span><span>Unit</span><span>Amount B</span><span>% B</span><span>Selisih</span></div>
                @foreach($items as $row)
                    @php $a=$row['A']??null;$b=$row['B']??null;$ta=(float)($a['total_price_idr']??0);$tb=(float)($b['total_price_idr']??0); @endphp
                    <div class="mc-detail-row"><span><b>{{ ($row['part_name']??'')?:($row['part_no']??'-') }}</b><small>{{ ($row['part_no']??'-') }} · {{ ($row['id_code']??'-') }}</small></span><span>{{ $a?number_format($a['qty_req'],0,',','.'):'-' }}</span><span>{{ $a['unit']??'-' }}</span><span class="amount-cell {{ $ta>$tb?'amount-higher':($ta<$tb?'amount-lower':'amount-equal') }}">{{ $a?'Rp '.number_format($ta,0,',','.'):'-' }}</span><span>{{ $grandA>0?number_format($ta/$grandA*100,1,',','.'):'0' }}%</span><span>{{ $b?number_format($b['qty_req'],0,',','.'):'-' }}</span><span>{{ $b['unit']??'-' }}</span><span class="amount-cell {{ $tb>$ta?'amount-higher':($tb<$ta?'amount-lower':'amount-equal') }}">{{ $b?'Rp '.number_format($tb,0,',','.'):'-' }}</span><span>{{ $grandB>0?number_format($tb/$grandB*100,1,',','.'):'0' }}%</span><span>{{ $ta-$tb>=0?'+':'' }}Rp {{ number_format($ta-$tb,0,',','.') }}</span></div>
                @endforeach
            </div>
        </details>
    @empty
        <div class="mc-empty">Belum ada data material untuk dibandingkan.</div>
    @endforelse
    @if($groups->isNotEmpty())<div class="mc-total"><strong>TOTAL MATERIAL</strong><span></span><span></span><b class="amount-cell {{ $grandA>$grandB?'amount-higher':($grandA<$grandB?'amount-lower':'amount-equal') }}">Rp {{ number_format($grandA,0,',','.') }}</b><b>100%</b><span></span><span></span><b class="amount-cell {{ $grandB>$grandA?'amount-higher':($grandB<$grandA?'amount-lower':'amount-equal') }}">Rp {{ number_format($grandB,0,',','.') }}</b><b>100%</b><b class="{{ $grandA-$grandB>0?'up':'down' }}">{{ $grandA-$grandB>=0?'+':'' }}Rp {{ number_format($grandA-$grandB,0,',','.') }}</b><span></span></div>@endif
</div>
<style>
.material-compare-wrap{display:none}.material-category-compare{padding:0 18px 18px}.mc-head,.mc-group>summary,.mc-total{display:grid;grid-template-columns:minmax(170px,1.8fr) .65fr .55fr 1fr .5fr .65fr .55fr 1fr .5fr 1fr 28px;align-items:center;gap:8px}.mc-head{padding:10px 12px;border-bottom:2px solid #244d72;background:#9fc2dd;color:#10233d;font-size:11px;font-weight:800}.mc-head span:not(:first-child){text-align:right}.mc-group{border-bottom:1px solid #dce5ed}.mc-group>summary{padding:12px;cursor:pointer;list-style:none;color:#17243a;font-size:11px}.mc-group>summary::-webkit-details-marker{display:none}.mc-group>summary:hover{background:#f4f8fb}.mc-group>summary span{text-align:right}.mc-group>summary i{display:grid;place-items:center;width:24px;height:24px;border-radius:6px;background:#edf3f8;color:#315e83;font-style:normal;transition:.2s}.mc-group[open]>summary{background:#eef6fc}.mc-group[open]>summary i{transform:rotate(180deg)}.mc-details{padding:6px 12px 10px 34px;background:#f8fafc}.mc-detail-head,.mc-detail-row{display:grid;grid-template-columns:minmax(200px,2fr) .7fr 1fr .7fr 1fr 1fr;gap:10px;align-items:center}.mc-detail-head{padding:7px 9px;color:#728297;font-size:9px;font-weight:800;text-transform:uppercase}.mc-detail-row{padding:9px;border-top:1px solid #e5ebf1;background:#fff;font-size:10px}.mc-detail-row span:not(:first-child){text-align:right}.mc-detail-row b,.mc-detail-row small{display:block}.mc-detail-row small{margin-top:2px;color:#7a8a9c;font-size:8px}.mc-total{padding:13px 12px;border-top:2px solid #244d72;font-size:11px}.mc-total b{text-align:right}.up{color:#c2410c!important}.down{color:#16834a!important}.mc-empty{padding:35px;text-align:center;color:#78899a}@media(max-width:1050px){.material-category-compare{overflow-x:auto}.mc-head,.mc-group>summary,.mc-total{min-width:1050px}.mc-details{min-width:1000px}}
.amount-cell{justify-self:end;width:max-content;padding:4px 7px;border-radius:6px}.amount-higher{background:#fee2e2;color:#b42318!important;font-weight:800}.amount-lower{background:#dcfce7;color:#15803d!important;font-weight:800}.amount-equal{background:transparent;color:inherit!important}
.mc-detail-head{padding:10px 9px!important;border-bottom:2px solid #244d72!important;background:#9fc2dd!important;color:#10233d!important;font-size:9px;font-weight:800;text-transform:uppercase}
.mc-detail-head,.mc-detail-row{grid-template-columns:minmax(190px,2fr) .7fr .65fr 1fr .55fr .7fr .65fr 1fr .55fr 1fr!important}
.mc-head,.mc-group>summary,.mc-total{grid-template-columns:minmax(120px,1.2fr) .65fr .55fr 1fr .5fr .65fr .55fr 1fr .5fr 1fr 28px!important}.mc-detail-head,.mc-detail-row{grid-template-columns:minmax(190px,2fr) .7fr .65fr 1fr .55fr .7fr .65fr 1fr .55fr 1fr!important}
.mc-detail-head>span:not(:first-child),.mc-detail-row>span:not(:first-child){justify-self:stretch!important;text-align:center!important}.mc-detail-row .amount-cell{justify-self:center!important}.mc-detail-head>span:first-child,.mc-detail-row>span:first-child{text-align:left!important}
</style>
