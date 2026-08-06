@extends('layouts.app')
@section('title',$a00->document_number)
@section('page-title','A00 — New Project Declaration')
@section('breadcrumb')<a href="{{ route('control-project.a00.index',absolute:false) }}">A00</a> / {{ $a00->document_number }} @endsection
@section('content')
<style>
.a00-toolbar{width:210mm;max-width:100%;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px}.a00-toolbar-group{display:flex;gap:8px;flex-wrap:wrap}.a00-download-group{display:flex;align-items:center;gap:8px}.a00-save-hint{max-width:180px;color:#64748b;font-size:10px;line-height:1.3}.a00-btn{border:0;border-radius:6px;background:#0b3478;color:#fff;padding:9px 14px;text-decoration:none;font-weight:700;cursor:pointer}.a00-btn.secondary{background:#fff;color:#0b3478;border:1px solid #0b3478}.a00-sheet{box-sizing:border-box;width:210mm;min-height:297mm;margin:auto;padding:6mm;background:#fff;border:1px solid #111;color:#000;font:10px Arial,sans-serif}.a00-sheet-inner{box-sizing:border-box;min-height:283mm;border:1px solid #000;padding:5mm 8mm}.a00-header{display:grid;grid-template-columns:34mm 68mm 25mm 1fr;height:23mm;border:1px solid #000}.a00-header>div{box-sizing:border-box;border-right:1px solid #000}.a00-header>div:last-child{border:0}.company-logo{display:flex;align-items:center;justify-content:center;padding:1mm;text-align:center}.company-logo img{display:block;width:27mm;height:20mm;max-width:100%;object-fit:contain}.a00-title{display:flex;align-items:center;justify-content:center;text-align:center;padding:2mm;font-size:16px;font-weight:800;line-height:1.25}.a00-code{display:flex;align-items:center;justify-content:center;font-size:29px;font-weight:900}.a00-meta{display:flex;align-items:center;font-size:6px}.a00-meta table{width:100%;height:100%;border-collapse:collapse}.a00-meta td{padding:1px 2px;border-bottom:1px solid #000;vertical-align:middle}.a00-meta tr:last-child td{border-bottom:0}.a00-meta td:first-child{width:17mm;border-right:1px solid #000;white-space:nowrap}.a00-meta td:last-child{font-weight:500;overflow-wrap:anywhere}.intro{margin:7mm 1mm}.doc-section{margin:0 9mm 7mm}.doc-section-title{display:grid;grid-template-columns:8mm 1fr;margin-bottom:4mm;font-weight:700}.general-list{margin-left:8mm}.general-item{margin-bottom:3mm}.general-line{display:grid;grid-template-columns:8mm 47mm 5mm minmax(0,1fr);align-items:end;min-height:5mm}.general-line .line-no{text-align:right;padding-right:3mm}.general-line .line-value{min-height:4mm;padding:0 2mm 1px;border-bottom:1px solid #000}.quantity-value{display:grid;grid-template-columns:auto 18mm 28mm;gap:3mm}.life-options{display:flex;gap:8mm;align-items:center}.box-mark{display:inline-flex;width:5mm;height:5mm;margin-right:2mm;border:1px solid #000;align-items:center;justify-content:center}.schedule-table{margin-left:8mm;border-collapse:collapse}.schedule-table td{height:5.5mm;padding:0 2mm}.schedule-table .schedule-no{width:7mm;text-align:right}.schedule-table .schedule-label{width:52mm}.schedule-table .colon{width:4mm}.schedule-date{border-collapse:collapse}.schedule-date td{height:5.5mm;padding:0 2mm;border:1px solid #000;text-align:center}.schedule-date .day{width:11mm}.schedule-date .month{width:17mm}.schedule-date .year{width:16mm}.date-hint{width:31mm;padding-left:8mm!important;white-space:nowrap}.issue-place{text-align:right;margin:11mm 0 8mm}.approval-table{width:90mm;margin-left:auto;border-collapse:collapse;text-align:center;font-size:9px}.approval-table th,.approval-table td{border:1px solid #000}.approval-table th{height:7mm;font-weight:400}.approval-mark{height:14mm;vertical-align:middle}.approval-name{height:7mm}.approved-stamp{display:inline-block;border:1.5px solid #119447;border-radius:2px;padding:3px 6px;color:#119447;font-size:8px;font-weight:800;letter-spacing:.06em;transform:rotate(-4deg)}.approval-mark img{max-width:26mm;max-height:13mm;object-fit:contain}.a00-note{margin-top:4mm;padding-top:2mm;border-top:1px solid #888;font-size:7px;line-height:1.45}.a00-footer-space{min-height:3mm}
@media(max-width:900px){.a00-sheet{width:100%;min-height:auto;padding:18px}.a00-header{grid-template-columns:22% 35% 16% 27%}.a00-title{font-size:15px}.a00-code{font-size:28px}.doc-section{margin-left:0;margin-right:0}}
@media print{@page{size:A4 portrait;margin:0}.sidebar,.sidebar-overlay,.header,.breadcrumb,.footer,.costing-assistant,.a00-toolbar{display:none!important}html,body,.app-wrapper,.main-wrapper,.main-content{margin:0!important;padding:0!important;width:auto!important;background:#fff!important}.a00-sheet{width:210mm;min-height:297mm;margin:0;padding:6mm;border:0}.main-content{zoom:1!important}}
</style>
@if(session('success'))<div style="width:210mm;max-width:100%;margin:0 auto 12px;background:#dcfce7;color:#166534;padding:10px;box-sizing:border-box">{{ session('success') }}</div>@endif
<div class="a00-toolbar"><div class="a00-toolbar-group"><a class="a00-btn secondary" href="{{ route('control-project.a00.index',absolute:false) }}">&larr; Kembali</a><a class="a00-btn" href="{{ route('project',absolute:false) }}">Buka Menu Project</a></div><div class="a00-download-group"><span class="a00-save-hint">Klik tombol, pilih <strong>Save as PDF</strong>, lalu klik Save untuk menentukan folder.</span><button class="a00-btn" type="button" onclick="downloadA00Pdf()">Simpan sebagai PDF</button></div></div>
<article class="a00-sheet">
 <div class="a00-sheet-inner">
    <header class="a00-header">
        <div class="company-logo"><img src="{{ asset('images/logo-dharma.svg') }}" alt="Logo PT Dharma Electrindo Manufacturing"></div>
        <div class="a00-title">NEW PROJECT<br>DECLARATION</div>
        <div class="a00-code">A00</div>
        <div class="a00-meta"><table><tr><td>Doc. No</td><td>{{ $a00->document_number }}</td></tr><tr><td>Date</td><td>{{ $a00->document_date->format('d-M-y') }}</td></tr><tr><td>Revision</td><td>{{ $a00->revision }}</td></tr><tr><td>From - To</td><td>{{ $a00->from_department }} - {{ $a00->to_department }}</td></tr></table></div>
    </header>
    <p class="intro">Dengan ini kami sampaikan project baru sebagai berikut:</p>
    <section class="doc-section"><div class="doc-section-title"><span>I.</span><span>General Information</span></div><div class="general-list">
        <div class="general-line"><span class="line-no">1.</span><span>Customer</span><span>:</span><span class="line-value">{{ $a00->customer }}</span></div>
        @foreach($a00->items as $item)<div class="general-item" @if(!$loop->first) style="margin-top:3mm;padding-top:2mm;border-top:1px dashed #aaa" @endif>
            <div class="general-line"><span class="line-no">2.</span><span>Model{{ $a00->items->count()>1?' '.$loop->iteration:'' }}</span><span>:</span><span class="line-value">{{ $item->model }}</span></div>
            <div class="general-line"><span class="line-no">3.</span><span>Assy Name</span><span>:</span><span class="line-value">{{ $item->assy_name }}</span></div>
            <div class="general-line"><span class="line-no">4.</span><span>Assy No.</span><span>:</span><span class="line-value">{{ $item->assy_number }}</span></div>
            <div class="general-line"><span class="line-no">5.</span><span>Quantity</span><span>:</span><span class="line-value quantity-value"><span>{{ $item->quantity!==null?number_format($item->quantity):'—' }}</span><span>{{ $item->quantity_uom }}</span><span>{{ $item->quantity_basis }}</span></span></div>
            <div class="general-line"><span class="line-no">6.</span><span>Product's Life</span><span>:</span><span class="line-value life-options"><span><i class="box-mark">{{ !$item->spot_order?'✓':'' }}</i>{{ $item->product_life_years!==null?$item->product_life_years.' Years':'—' }}</span><span><i class="box-mark">{{ $item->spot_order?'✓':'' }}</i>Spot Order</span></span></div>
        </div>@endforeach
    </div></section>
    @php $internal=['due_part_list'=>'Due Date Part List','due_umh'=>'Due Date UMH','due_new_part_price'=>'Due Date Harga New Part','due_costing'=>'Due Date Costing','due_submit_quotation'=>'Submit Quotation'];$customerEvents=['pp1_date'=>'PP1','pp2_date'=>'PP2','pp3_date'=>'PP3','sop_mp_date'=>'SOP/MP']; @endphp
    <section class="doc-section"><div class="doc-section-title"><span>II.</span><span>Schedule of Internal Project</span></div><table class="schedule-table"><tbody>@foreach($internal as $field=>$label)@php $date=$a00->$field; @endphp<tr><td class="schedule-no">{{ $loop->iteration }}.</td><td class="schedule-label">{{ $label }}</td><td class="colon">:</td><td><table class="schedule-date"><tr><td class="day">{{ $date?->format('d') }}</td><td class="month">{{ $date?->format('M') }}</td><td class="year">{{ $date?->format('Y') }}</td></tr></table></td><td class="date-hint">(dd/mmm/yyyy)</td></tr>@endforeach</tbody></table></section>
    <section class="doc-section"><div class="doc-section-title"><span>III.</span><span>Schedule of Customer Events</span></div><table class="schedule-table"><tbody>@foreach($customerEvents as $field=>$label)@php $date=$a00->$field;$isTba=$field==='sop_mp_date'&&$a00->sop_mp_tba; @endphp<tr><td class="schedule-no">{{ $loop->iteration }}.</td><td class="schedule-label">{{ $label }}</td><td class="colon">:</td><td><table class="schedule-date"><tr>@if($isTba)<td class="day"></td><td class="month"></td><td class="year">TBA</td>@else<td class="day">{{ $date?->format('d') }}</td><td class="month">{{ $date?->format('M') }}</td><td class="year">{{ $date?->format('Y') }}</td>@endif</tr></table></td><td class="date-hint">(dd/mmm/yyyy)</td></tr>@endforeach</tbody></table></section>
    <div class="issue-place">{{ $a00->issue_location }}, {{ $a00->document_date->translatedFormat('d F Y') }}</div>
    <table class="approval-table"><thead><tr><th>Disetujui</th><th>Diketahui</th><th>Dibuat</th></tr></thead><tbody><tr><td class="approval-mark">@if($a00->approved_signature_path)<img src="{{ Storage::url($a00->approved_signature_path) }}" alt="TTD">@else<span class="approved-stamp">APPROVED</span>@endif</td><td class="approval-mark">@if($a00->acknowledged_signature_path)<img src="{{ Storage::url($a00->acknowledged_signature_path) }}" alt="TTD">@else<span class="approved-stamp">APPROVED</span>@endif</td><td class="approval-mark">@if($a00->prepared_signature_path)<img src="{{ Storage::url($a00->prepared_signature_path) }}" alt="TTD">@else<span class="approved-stamp">APPROVED</span>@endif</td></tr><tr><td class="approval-name">{{ $a00->approved_by ?: '—' }}</td><td class="approval-name">{{ $a00->acknowledged_by ?: '—' }}</td><td class="approval-name">{{ $a00->prepared_by ?: '—' }}</td></tr></tbody></table>
    <div class="a00-footer-space"></div><div class="a00-note"><strong>NOTE:</strong><br>1. Jumlah assy no. dan part no. dapat dilampirkan pada halaman terpisah.<br>2. Jika ada Die Go / LoI dapat dilampirkan pada dokumen ini.</div>
 </div>
</article>
@php
    $downloadFileName = 'A00 - '.str_replace(
        ['<', '>', ':', '"', '/', '\\', '|', '?', '*'],
        '-',
        $a00->document_number
    );
@endphp
<script>
function downloadA00Pdf(){
    const originalTitle=document.title;
    const fileName=@json($downloadFileName);
    document.title=fileName;
    const restoreTitle=()=>{document.title=originalTitle;window.removeEventListener('afterprint',restoreTitle)};
    window.addEventListener('afterprint',restoreTitle);
    window.print();
    window.setTimeout(restoreTitle,30000);
}
</script>
@endsection
