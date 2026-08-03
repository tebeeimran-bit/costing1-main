@extends('layouts.app')

@section('title', 'Registrasi Document Control')
@section('page-title', 'Registrasi Document Control')
@section('breadcrumb')<a href="{{ route('dashboard', absolute: false) }}">Dashboard</a> / Registrasi Document Control @endsection

@section('content')
@if(request()->boolean('embedded'))
<style>html,body{margin:0!important;width:100%!important;min-width:0!important;background:#f8fafc!important;zoom:1!important}.sidebar,.sidebar-overlay,.header,.breadcrumb,.footer,.costing-assistant{display:none!important}.app-wrapper,.main-wrapper,.main-content{display:block!important;width:100%!important;min-width:0!important;min-height:0!important;margin:0!important;padding:0!important}.dc-toolbar,.dc-list-card,.excel-window,.dc-pagination{display:none!important}.dc-modal{width:100%!important;height:100vh!important;max-width:none!important;max-height:none!important;border-radius:0!important}</style>
@endif
@php
$columns = [
 'drawing_type'=>'Drawing Type','registration_no'=>'Reg. No','registration_date'=>'Date','customer'=>'Customer','project'=>'Project',
 'a00'=>'A00','a04'=>'A04','a05'=>'A05','vm'=>'V/M','years'=>'Years','part_number'=>'Part Number','part_name'=>'Part Name',
 'revision_number'=>'Rev. Number','revision_record'=>'Rev. Record','page'=>'Page','drawing_remark'=>'Remark',
 'pd_distribution'=>'PD Distribution','qa_distribution'=>'QA Distribution','pnp_qt_distribution'=>'PNP & QT Distribution',
 'ppe_pme_distribution'=>'PPE/PME Distribution','pd_return'=>'PD Return','qa_return'=>'QA Return','pnp_return'=>'PNP Return',
 'ppe_pme_return'=>'PPE/PME Return','return_remark'=>'Remark','return_date'=>'Date of Return','crusher_remark'=>'Remark',
 'crusher_date'=>'Date of Crusher','drawing_status'=>'Drawing Status','business_category'=>'Kategori Bisnis'
];
$dateFields = ['registration_date','pd_distribution','qa_distribution','pnp_qt_distribution','ppe_pme_distribution','pd_return','qa_return','pnp_return','ppe_pme_return','return_date','crusher_date'];
$formGroups = [
 'Informasi Drawing' => ['drawing_type','registration_no','registration_date','customer','project','a00','a04','a05','vm','years','part_number','part_name','revision_number','revision_record','page','drawing_remark'],
 'Distribusi Dokumen' => ['pd_distribution','qa_distribution','pnp_qt_distribution','ppe_pme_distribution'],
 'Pengembalian Dokumen' => ['pd_return','qa_return','pnp_return','ppe_pme_return','return_remark','return_date'],
 'Crusher & Status' => ['crusher_remark','crusher_date','drawing_status','business_category'],
];
$excelLetters = array_merge(range('B', 'Z'), ['AA','AB','AC','AD','AE']);
$columnWidths = [150,70,86,190,145,62,62,62,55,65,210,250,82,115,78,150,95,95,115,125,95,95,105,115,120,105,110,110,150,150];
$toExcelLetter = function (int $number): string { $result=''; while($number>0){$number--; $result=chr(65+($number%26)).$result; $number=intdiv($number,26);} return $result; };
foreach($customColumns as $index=>$customColumn){ $excelLetters[]=$toExcelLetter(32+$index); $columnWidths[]=$customColumn->width; }
$worksheetWidth = 42 + array_sum($columnWidths);
@endphp
<style>
.dc-toolbar{display:flex;gap:.65rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem}.dc-toolbar form{display:flex;gap:.5rem;flex-wrap:wrap}.dc-input{border:1px solid #cbd5e1;border-radius:7px;padding:.52rem .65rem;background:#fff;font:inherit;font-size:.8rem}.dc-btn{border:0;border-radius:7px;padding:.56rem .85rem;font-weight:700;font-size:.78rem;cursor:pointer;background:#0b3478;color:#fff}.dc-btn.secondary{background:#fff;color:#0b3478;border:1px solid #0b3478}.dc-btn.danger{background:#dc2626}.dc-alert{padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;background:#dcfce7;color:#166534}.dc-sheet{width:100%;overflow:auto;border:1px solid #94a3b8;max-height:calc(100vh - 270px);background:#fff}.dc-table{border-collapse:separate;border-spacing:0;min-width:3900px;font-size:11px}.dc-table th{position:sticky;top:0;z-index:3;background:#082d70;color:#fff;text-align:center;white-space:normal;height:54px;padding:6px;border-right:1px solid #dbeafe}.dc-table td{height:25px;padding:4px 6px;white-space:nowrap;border-right:1px solid #e2e8f0;border-bottom:1px solid #eef2f7;max-width:260px;overflow:hidden;text-overflow:ellipsis}.dc-table tbody tr:hover td{background:#fdf2f8!important}.dc-table td:nth-child(n+7):nth-child(-n+9){background:#edf3df}.dc-table td:nth-child(n+18):nth-child(-n+22){background:#e8e4f0}.dc-table td:nth-child(n+23):nth-child(-n+26){background:#ffead9}.dc-table td:nth-child(28){background:#ce676b;color:#fff}.dc-action{position:sticky;left:0;background:#fff!important;z-index:2}.dc-pagination{margin-top:.75rem}.dc-modal{border:0;border-radius:12px;padding:0;width:min(1100px,95vw);max-height:90vh}.dc-modal::backdrop{background:rgba(15,23,42,.65)}.dc-modal-head{padding:1rem 1.25rem;background:#082d70;color:#fff;display:flex;justify-content:space-between}.dc-modal-body{padding:1rem 1.25rem;overflow:auto;max-height:72vh}.dc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem}.dc-field label{font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:.3rem}.dc-field input{width:100%;box-sizing:border-box}.dc-modal-foot{padding:1rem 1.25rem;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:.5rem}@media(max-width:800px){.dc-grid{grid-template-columns:repeat(2,1fr)}}
.dc-modal{position:fixed;inset:auto;top:50%;left:50%;transform:translate(-50%,-50%);margin:0;width:min(1120px,96vw);height:min(880px,92vh);max-height:none;overflow:hidden;background:#f8fafc;box-shadow:0 24px 70px rgba(15,23,42,.3)}.dc-modal>form:first-child{height:100%;display:flex;flex-direction:column}.dc-modal-head{padding:.9rem 1.25rem;align-items:center;flex:0 0 auto}.dc-modal-close{width:30px;height:30px;border:0;border-radius:6px;background:rgba(255,255,255,.1);color:#fff;font-size:20px;cursor:pointer}.dc-modal-close:hover{background:rgba(255,255,255,.22)}.dc-modal-body{padding:1.1rem 1.25rem 1.5rem;max-height:none;overflow:auto;flex:1 1 auto}.dc-section{background:#fff;border:1px solid #dbe3ef;border-radius:10px;margin-bottom:1rem;overflow:hidden}.dc-section:last-child{margin-bottom:0}.dc-section-title{padding:.58rem .85rem;background:#edf3fb;border-bottom:1px solid #dbe3ef;color:#123b75;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.dc-section:nth-child(2) .dc-section-title{background:#eeeaf5;color:#5b4679}.dc-section:nth-child(3) .dc-section-title{background:#fff0e4;color:#8b4b20}.dc-section:nth-child(4) .dc-section-title{background:#f9e7e8;color:#8d3035}.dc-section .dc-grid{gap:.9rem;padding:.9rem}.dc-field input{height:38px;transition:border-color .15s,box-shadow .15s}.dc-field input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);outline:none}.dc-field-wide{grid-column:span 2}.dc-required{color:#dc2626}.dc-modal-foot{padding:.85rem 1.25rem;background:#fff;align-items:center;flex:0 0 auto}@media(max-width:800px){.dc-modal{height:94vh}.dc-field-wide{grid-column:span 2}}@media(max-width:520px){.dc-grid{grid-template-columns:1fr}.dc-field-wide{grid-column:span 1}}
.excel-window{border:1px solid #9aa5b1;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.12)}.excel-formula{height:31px;display:flex;align-items:center;background:#f3f3f3;border-bottom:1px solid #b8b8b8;font-family:Calibri,Arial,sans-serif;font-size:12px}.excel-name-box{width:72px;height:100%;display:flex;align-items:center;padding:0 8px;border-right:1px solid #b8b8b8;background:#fff}.excel-fx{width:34px;text-align:center;font-style:italic;font-weight:700;color:#666}.excel-formula-value{flex:1;height:22px;line-height:22px;padding:0 8px;background:#fff;border:1px solid #c8c8c8;margin-right:5px;overflow:hidden;white-space:nowrap}.dc-sheet{border:0;max-height:calc(100vh - 315px);font-family:Calibri,"Segoe UI",Arial,sans-serif}.dc-table{table-layout:fixed;min-width:0;width:max-content;font-size:11px}.dc-table .excel-letters th{position:sticky;top:0;z-index:6;height:22px;padding:0;background:#e6e6e6;color:#222;border-right:1px solid #a6a6a6;border-bottom:1px solid #8f8f8f;font-weight:400}.dc-table .excel-headers th{top:22px;z-index:5;height:48px;padding:4px 18px 4px 5px;line-height:13px;position:sticky}.excel-corner{left:0!important;z-index:8!important;width:42px;background:#d8d8d8!important}.excel-row-head{position:sticky;left:0;z-index:3;text-align:center!important;background:#e6e6e6!important;color:#222!important;border-right:1px solid #999!important;padding:0!important}.excel-row-btn{width:100%;height:100%;border:0;background:transparent;font:inherit;color:inherit;cursor:pointer}.excel-row-btn:hover{background:#cfe8cf}.excel-filter{position:absolute;right:3px;bottom:5px;width:14px;height:14px;background:#f4f4f4;border:1px solid #c0c0c0;color:#333;font-size:8px;line-height:11px;text-align:center}.dc-table td{height:22px;padding:1px 5px;border-right:1px solid #d9e2f0;border-bottom:1px solid #d9e2f0;background:#fff}.dc-table tbody tr:nth-child(18n+6) td:not(.excel-row-head){background:#fff5fb}.dc-table td:nth-child(n+7):nth-child(-n+9){background:#edf4df}.dc-table td:nth-child(n+18):nth-child(-n+22){background:#e9e5f1}.dc-table td:nth-child(n+23):nth-child(-n+26){background:#ffead9}.dc-table td:nth-child(28){background:#ce676b;color:#fff}.excel-tabs{height:31px;display:flex;align-items:end;gap:2px;background:#f3f3f3;border-top:1px solid #aaa;padding-left:45px}.excel-tab{height:27px;padding:0 24px;border:0;border-bottom:3px solid #217346;background:#fff;color:#222;font:12px Calibri,Arial;cursor:default}.excel-status{margin-left:auto;padding:0 12px 7px;color:#555;font:11px Calibri,Arial}.dc-table tbody tr:hover td:not(.excel-row-head){outline:1px solid #70ad47;outline-offset:-1px}.dc-table tbody tr:hover .excel-row-head{background:#c6e0b4!important}
.dc-table{min-width:{{ $worksheetWidth }}px!important;width:{{ $worksheetWidth }}px!important}.dc-table td:nth-child(29){background:#ce676b;color:#fff}.excel-cell{cursor:cell;outline:none}.excel-cell:hover{box-shadow:inset 0 0 0 1px #70ad47}.excel-cell.is-selected{box-shadow:inset 0 0 0 2px #217346!important;position:relative;z-index:2}.excel-cell.is-saving{background:#fff4ce!important}.excel-cell.is-error{box-shadow:inset 0 0 0 2px #dc2626!important;background:#fee2e2!important}.excel-formula-value{outline:none}.excel-formula-value:focus{border-color:#217346;box-shadow:inset 0 0 0 1px #217346}.excel-status-saving{color:#9a6700}.excel-status-error{color:#b91c1c;font-weight:700}.excel-context{position:fixed;z-index:9999;min-width:190px;background:#fff;border:1px solid #aaa;box-shadow:2px 4px 12px rgba(0,0,0,.22);padding:4px 0;font:12px Calibri,Arial}.excel-context[hidden]{display:none}.excel-context button{display:block;width:100%;padding:7px 14px;border:0;background:#fff;text-align:left;cursor:pointer}.excel-context button:hover{background:#e5f1fb}.excel-context hr{border:0;border-top:1px solid #ddd;margin:4px 0}.custom-column-head{background:#174a8b!important}
.dc-list-card{background:#fff;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06)}.dc-list-summary{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;border-bottom:1px solid #e2e8f0}.dc-list-summary strong{color:#0f2f64}.dc-record-count{font-size:.76rem;color:#64748b;background:#f1f5f9;padding:.3rem .6rem;border-radius:999px}.dc-list-scroll{overflow:auto;max-height:calc(100vh - 300px)}.dc-list{width:100%;border-collapse:separate;border-spacing:0;font-size:.78rem}.dc-list th{position:sticky;top:0;z-index:2;background:#0b3478;color:#fff;text-align:left;padding:.7rem .75rem;white-space:nowrap}.dc-list td{padding:.65rem .75rem;border-bottom:1px solid #edf2f7;color:#334155;vertical-align:middle}.dc-list tbody tr{cursor:pointer;transition:background .12s}.dc-list tbody tr:hover{background:#f5f9ff}.dc-main{font-weight:700;color:#0f2f64}.dc-sub{display:block;font-size:.7rem;color:#94a3b8;margin-top:2px}.dc-badge{display:inline-flex;padding:.25rem .55rem;border-radius:999px;font-size:.68rem;font-weight:700;white-space:nowrap}.dc-badge-new{background:#dcfce7;color:#166534}.dc-badge-revision{background:#fef3c7;color:#92400e}.dc-badge-default{background:#e2e8f0;color:#475569}.dc-action-btn{border:1px solid #b9c7da;background:#fff;color:#0b3478;border-radius:6px;padding:.3rem .6rem;font-weight:700;cursor:pointer}.dc-toolbar{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:.75rem}
</style>

@if(session('success'))<div class="dc-alert">{{ session('success') }}</div>@endif
@if($errors->any())<div class="dc-alert" style="background:#fee2e2;color:#991b1b">{{ $errors->first() }}</div>@endif

<div class="dc-toolbar">
 <button class="dc-btn" onclick="openRegistration()">+ Tambah Registrasi</button>
 <form method="GET">
  <input class="dc-input" name="q" value="{{ request('q') }}" placeholder="Cari reg. no, customer, project, part..." style="width:260px">
  @foreach(['customer'=>'Customer','drawing_status'=>'Drawing Status','business_category'=>'Kategori Bisnis'] as $field=>$label)
  <select class="dc-input" name="{{ $field }}"><option value="">Semua {{ $label }}</option>@foreach($options[$field] as $option)<option @selected(request($field)===$option)>{{ $option }}</option>@endforeach</select>
  @endforeach
  <button class="dc-btn secondary">Filter</button>
 </form>
 <form method="POST" action="{{ route('document-control.import', absolute:false) }}" enctype="multipart/form-data" style="margin-left:auto">@csrf
  <input class="dc-input" type="file" name="file" accept=".xlsx,.xls" required><button class="dc-btn">Import Excel</button>
 </form>
</div>

<div class="dc-list-card">
 <div class="dc-list-summary"><strong>Daftar Registrasi Drawing</strong><span class="dc-record-count">{{ $rows->total() }} dokumen</span></div>
 <div class="dc-list-scroll"><table class="dc-list">
  <thead><tr><th>Reg. No</th><th>Tanggal</th><th>Customer / Project</th><th>Part Number</th><th>Part Name</th><th>Rev.</th><th>Drawing Status</th><th>Kategori Bisnis</th><th></th></tr></thead>
  <tbody>@forelse($rows as $row)
   <tr onclick='editRegistration(@json($row))'>
    <td><span class="dc-main">{{ $row->registration_no ?: '—' }}</span><span class="dc-sub">{{ $row->drawing_type }}</span></td>
    <td>{{ optional($row->registration_date)->format('d M Y') ?: '—' }}</td>
    <td><span class="dc-main">{{ $row->customer ?: '—' }}</span><span class="dc-sub">Project: {{ $row->project ?: '—' }}</span></td>
    <td class="dc-main">{{ $row->part_number ?: '—' }}</td><td>{{ $row->part_name ?: '—' }}</td><td>{{ $row->revision_number ?: '0' }}</td>
    <td><span class="dc-badge {{ $row->drawing_status==='New Drawing'?'dc-badge-new':($row->drawing_status==='Revisi Drawing'?'dc-badge-revision':'dc-badge-default') }}">{{ $row->drawing_status ?: 'Belum ditentukan' }}</span></td>
    <td>{{ $row->business_category ?: '—' }}</td><td><button class="dc-action-btn" type="button">Detail</button></td>
   </tr>
  @empty<tr><td colspan="9" style="padding:2rem;text-align:center;color:#64748b">Belum ada data registrasi.</td></tr>@endforelse</tbody>
 </table></div>
</div>
<div hidden>
<div class="excel-window">
<div class="excel-formula"><div class="excel-name-box" id="excelCellName">B2</div><div class="excel-fx">fx</div><div class="excel-formula-value" id="excelFormulaValue">Pilih baris untuk melihat atau mengedit data</div></div>
<div class="dc-sheet">
 <table class="dc-table">
  <colgroup><col style="width:42px">@foreach($columnWidths as $width)<col style="width:{{ $width }}px">@endforeach</colgroup>
  <thead>
   <tr class="excel-letters"><th class="excel-corner"></th>@foreach($excelLetters as $letter)<th>{{ $letter }}</th>@endforeach</tr>
   <tr class="excel-headers"><th class="excel-corner">1</th>@foreach($columns as $label)<th>{{ $label }}<span class="excel-filter">▼</span></th>@endforeach @foreach($customColumns as $customColumn)<th class="custom-column-head" data-column-id="{{ $customColumn->id }}" data-column-name="{{ $customColumn->name }}">{{ $customColumn->name }}<span class="excel-filter">▼</span></th>@endforeach</tr>
  </thead>
  <tbody>
  @forelse($rows as $row)
   <tr>
    <td class="excel-row-head" data-row-id="{{ $row->id }}" title="Klik untuk form, klik kanan untuk sisipkan baris"><button class="excel-row-btn" onclick='editRegistration(@json($row))'>{{ ($rows->currentPage()-1)*$rows->perPage()+$loop->iteration+1 }}</button></td>
    @foreach($columns as $field=>$label)
     <td class="excel-cell" contenteditable="true" spellcheck="false" data-id="{{ $row->id }}" data-field="{{ $field }}" data-column="{{ $excelLetters[$loop->index] }}" data-original="{{ in_array($field,$dateFields) ? optional($row->$field)->format('d-M-y') : $row->$field }}" title="Klik untuk edit langsung">{{ in_array($field,$dateFields) ? optional($row->$field)->format('d-M-y') : $row->$field }}</td>
    @endforeach
    @php $customCellValues=$row->customCells->keyBy('column_id'); @endphp
    @foreach($customColumns as $customIndex=>$customColumn)
     @php $customValue=optional($customCellValues->get($customColumn->id))->value; @endphp
     <td class="excel-cell" contenteditable="true" spellcheck="false" data-id="{{ $row->id }}" data-custom-column="{{ $customColumn->id }}" data-column="{{ $excelLetters[30+$customIndex] }}" data-original="{{ $customValue }}">{{ $customValue }}</td>
    @endforeach
   </tr>
  @empty<tr><td class="excel-row-head">2</td><td colspan="{{ 30+$customColumns->count() }}" style="padding:2rem;text-align:center">Belum ada data registrasi.</td></tr>@endforelse
  </tbody>
 </table>
</div>
<div class="excel-context" id="rowContext" hidden><button data-action="above">Sisipkan baris di atas</button><button data-action="below">Sisipkan baris di bawah</button><hr><button data-action="edit">Edit form lengkap</button><button data-action="delete">Hapus baris</button></div>
<div class="excel-context" id="columnContext" hidden><button data-action="add">Tambah kolom di akhir</button><button data-action="rename">Ubah nama kolom</button><button data-action="resize">Ubah lebar kolom</button><hr><button data-action="delete">Hapus kolom</button></div>
<div class="excel-tabs"><button class="excel-tab">Registrasi Drawing</button><span class="excel-status" id="excelSaveStatus">Ready &nbsp; | &nbsp; {{ $rows->total() }} records</span></div>
</div>
</div>
<div class="dc-pagination">{{ $rows->links() }}</div>

<dialog id="registrationModal" class="dc-modal">
 <form id="registrationForm" method="POST" action="{{ route('document-control.store', absolute:false) }}">@csrf <input id="methodField" type="hidden" name="_method" value="POST"><input id="workflowTaskId" type="hidden" name="workflow_task_id" value="{{ $workflowTask?->id }}">
  <div class="dc-modal-head"><strong id="modalTitle">Tambah Registrasi Document Control</strong><button class="dc-modal-close" type="button" aria-label="Tutup" onclick="registrationModal.close()">&times;</button></div>
  <div class="dc-modal-body">
   @foreach($formGroups as $groupName=>$groupFields)
   <section class="dc-section">
    <div class="dc-section-title">{{ $groupName }}</div>
    <div class="dc-grid">
     @foreach($groupFields as $field)
     <div class="dc-field {{ in_array($field, ['customer','part_name','drawing_remark','return_remark','crusher_remark']) ? 'dc-field-wide' : '' }}">
      <label for="f_{{ $field }}">{{ $columns[$field] }} @if($field==='registration_no')<span class="dc-required">*</span>@endif</label>
      <input class="dc-input" id="f_{{ $field }}" name="{{ $field }}" type="{{ in_array($field,$dateFields) ? 'date':'text' }}" {{ $field==='registration_no'?'required':'' }}>
     </div>
     @endforeach
    </div>
   </section>
   @endforeach
  </div>
  <div class="dc-modal-foot"><button type="button" id="deleteButton" class="dc-btn danger" style="display:none;margin-right:auto" onclick="deleteRegistration()">Hapus</button><button type="button" class="dc-btn secondary" onclick="registrationModal.close()">Batal</button><button class="dc-btn">Simpan</button></div>
 </form>
 <form id="deleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
</dialog>
@php
    $workflowPrefillData = $workflowTask ? [
        'id' => $workflowTask->id,
        'customer' => $workflowTask->project->customer,
        'project' => $workflowTask->project->model,
        'a00' => 'ada',
        'part_number' => $workflowTask->project->part_number,
        'part_name' => $workflowTask->project->part_name,
        'revision_number' => $workflowTask->revision->version_label,
        'drawing_status' => 'New Drawing',
        'business_category' => optional($workflowTask->project->product)->name ?? '',
    ] : null;
@endphp
<script>
const registrationModal=document.getElementById('registrationModal'), registrationForm=document.getElementById('registrationForm');
const fields=@json(array_keys($columns)); const dateFields=@json($dateFields); let activeId=null;
const workflowPrefill=@json($workflowPrefillData);
function openRegistration(prefill=null){activeId=null;registrationForm.reset();registrationForm.action=@json(route('document-control.store',absolute:false));methodField.value='POST';modalTitle.textContent='Tambah Registrasi Document Control';deleteButton.style.display='none';if(prefill){workflowTaskId.value=prefill.id||'';Object.entries(prefill).forEach(([field,value])=>{const input=document.getElementById('f_'+field);if(input)input.value=value??''});document.getElementById('f_registration_date').value=new Date().toISOString().slice(0,10);modalTitle.textContent='Proses Drawing — '+(prefill.part_number||'Project')}registrationModal.showModal()}
function editRegistration(row){activeId=row.id;registrationForm.reset();fields.forEach(f=>{const el=document.getElementById('f_'+f);if(el){let value=row[f]??'';if(dateFields.includes(f)&&value)value=String(value).slice(0,10);el.value=value}});registrationForm.action=@json(url('/document-control/registrations'))+'/'+row.id;methodField.value='PUT';modalTitle.textContent='Edit Registrasi '+(row.registration_no||'');deleteButton.style.display='block';registrationModal.showModal()}
function deleteRegistration(){if(!activeId||!confirm('Hapus registrasi ini?'))return;deleteForm.action=@json(url('/document-control/registrations'))+'/'+activeId;deleteForm.submit()}
if(workflowPrefill) openRegistration(workflowPrefill);
@if($editRegistration)
editRegistration(@json($editRegistration));
@endif
@if(request()->boolean('embedded'))
registrationModal.addEventListener('close',()=>parent.postMessage({type:'registration-detail-close'},location.origin));
@endif

const inlineCells=[...document.querySelectorAll('.excel-cell')];
const formulaValue=document.getElementById('excelFormulaValue');
const cellName=document.getElementById('excelCellName');
const saveStatus=document.getElementById('excelSaveStatus');
let selectedCell=null;

function selectCell(cell){
 if(selectedCell)selectedCell.classList.remove('is-selected');
 selectedCell=cell;cell.classList.add('is-selected');
 cellName.textContent=cell.dataset.column+cell.parentElement.rowIndex;
 formulaValue.textContent=cell.textContent.trim();
}
async function saveCell(cell){
 const value=cell.textContent.trim();
 if(value===cell.dataset.original)return;
 cell.classList.remove('is-error');cell.classList.add('is-saving');
 saveStatus.textContent='Saving...';saveStatus.className='excel-status excel-status-saving';
 try{
  const isCustom=Boolean(cell.dataset.customColumn);
  const endpoint=@json(url('/document-control/registrations'))+'/'+cell.dataset.id+(isCustom?'/custom-cell':'/cell');
  const payload=isCustom?{column_id:cell.dataset.customColumn,value:value}:{field:cell.dataset.field,value:value};
  const response=await fetch(endpoint,{
   method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
   body:JSON.stringify(payload)
  });
  const result=await response.json();
  if(!response.ok)throw new Error(result.message||Object.values(result.errors||{})[0]?.[0]||'Gagal menyimpan');
  cell.textContent=result.value??'';cell.dataset.original=cell.textContent.trim();
  formulaValue.textContent=cell.textContent.trim();saveStatus.textContent='Saved | '+@json($rows->total())+' records';saveStatus.className='excel-status';
 }catch(error){cell.classList.add('is-error');saveStatus.textContent=error.message;saveStatus.className='excel-status excel-status-error'}
 finally{cell.classList.remove('is-saving')}
}
inlineCells.forEach(cell=>{
 cell.addEventListener('focus',()=>selectCell(cell));
 cell.addEventListener('blur',()=>saveCell(cell));
 cell.addEventListener('keydown',event=>{
  if(event.key==='Enter'){event.preventDefault();cell.blur();const next=inlineCells[inlineCells.indexOf(cell)+1];if(next)next.focus()}
  if(event.key==='Tab'){event.preventDefault();cell.blur();const direction=event.shiftKey?-1:1;const next=inlineCells[inlineCells.indexOf(cell)+direction];if(next)next.focus()}
  if(event.key==='Escape'){event.preventDefault();cell.textContent=cell.dataset.original;cell.blur()}
 });
 cell.addEventListener('paste',event=>{event.preventDefault();document.execCommand('insertText',false,event.clipboardData.getData('text/plain').replace(/[\r\n]+/g,' '))});
});
formulaValue.setAttribute('contenteditable','true');
formulaValue.addEventListener('input',()=>{if(selectedCell)selectedCell.textContent=formulaValue.textContent});
formulaValue.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();if(selectedCell){selectedCell.textContent=formulaValue.textContent;saveCell(selectedCell)}}if(event.key==='Escape'&&selectedCell){formulaValue.textContent=selectedCell.dataset.original;selectedCell.textContent=selectedCell.dataset.original}});

const csrfToken=document.querySelector('meta[name="csrf-token"]').content;
async function jsonRequest(url,method,body={}){
 const response=await fetch(url,{method,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(body)});
 const result=await response.json();if(!response.ok)throw new Error(result.message||'Operasi gagal');return result;
}
async function insertRow(referenceId,position){
 try{saveStatus.textContent='Adding row...';await jsonRequest(@json(route('document-control.rows.insert',absolute:false)),'POST',{reference_id:referenceId,position});location.reload()}catch(error){alert(error.message)}
}
async function addCustomColumn(){
 const name=prompt('Nama kolom baru:');if(!name?.trim())return;
 try{await jsonRequest(@json(route('document-control.columns.store',absolute:false)),'POST',{name:name.trim(),width:140});location.reload()}catch(error){alert(error.message)}
}
const rowContext=document.getElementById('rowContext'),columnContext=document.getElementById('columnContext');let contextRow=null,contextColumn=null;
function showContext(menu,event){event.preventDefault();document.querySelectorAll('.excel-context').forEach(el=>el.hidden=true);menu.hidden=false;menu.style.left=Math.min(event.clientX,innerWidth-210)+'px';menu.style.top=Math.min(event.clientY,innerHeight-190)+'px'}
document.querySelectorAll('.excel-row-head[data-row-id]').forEach(head=>head.addEventListener('contextmenu',event=>{contextRow={id:head.dataset.rowId,row:head.closest('tr')};showContext(rowContext,event)}));
document.querySelectorAll('.custom-column-head').forEach(head=>head.addEventListener('contextmenu',event=>{contextColumn={id:head.dataset.columnId,name:head.dataset.columnName};showContext(columnContext,event)}));
document.addEventListener('click',event=>{if(!event.target.closest('.excel-context'))document.querySelectorAll('.excel-context').forEach(el=>el.hidden=true)});
rowContext.addEventListener('click',async event=>{const action=event.target.dataset.action;if(!action||!contextRow)return;rowContext.hidden=true;
 if(action==='above'||action==='below')return insertRow(contextRow.id,action);
 if(action==='edit')return contextRow.row.querySelector('.excel-row-btn').click();
 if(action==='delete'&&confirm('Hapus baris ini?')){try{await jsonRequest(@json(url('/document-control/registrations'))+'/'+contextRow.id,'DELETE');location.reload()}catch(error){alert(error.message)}}
});
columnContext.addEventListener('click',async event=>{const action=event.target.dataset.action;if(!action)return;columnContext.hidden=true;if(action==='add')return addCustomColumn();if(!contextColumn)return;
 try{
  if(action==='rename'){const name=prompt('Nama kolom:',contextColumn.name);if(name?.trim())await jsonRequest(@json(url('/document-control/columns'))+'/'+contextColumn.id,'PATCH',{name:name.trim()});else return}
  if(action==='resize'){const width=prompt('Lebar kolom (60-500 px):','140');if(width)await jsonRequest(@json(url('/document-control/columns'))+'/'+contextColumn.id,'PATCH',{name:contextColumn.name,width:Number(width)});else return}
  if(action==='delete'){if(!confirm('Hapus kolom dan seluruh isinya?'))return;await jsonRequest(@json(url('/document-control/columns'))+'/'+contextColumn.id,'DELETE')}
  location.reload();
 }catch(error){alert(error.message)}
});
</script>
@endsection
