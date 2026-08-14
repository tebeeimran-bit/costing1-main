<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentControlRegistration;
use App\Models\DocumentControlColumn;
use App\Models\DocumentControlCustomCell;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\ProjectWorkflowTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Support\BusinessCategoryContext;

class DocumentControlRegistrationController extends Controller
{
    private const FIELDS = [
        'drawing_type','registration_no','registration_date','customer','project','a00','a04','a05','vm','years',
        'part_number','part_name','revision_number','revision_record','page','drawing_remark','pd_distribution',
        'qa_distribution','pnp_qt_distribution','ppe_pme_distribution','pd_return','qa_return','pnp_return',
        'ppe_pme_return','return_remark','return_date','crusher_remark','crusher_date','drawing_status','business_category',
    ];

    private const DATE_FIELDS = ['registration_date','pd_distribution','qa_distribution','pnp_qt_distribution','ppe_pme_distribution','pd_return','qa_return','pnp_return','ppe_pme_return','return_date','crusher_date'];

    public function index(Request $request)
    {
        $query = DocumentControlRegistration::query();
        BusinessCategoryContext::apply($query);
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                foreach (['registration_no','customer','project','part_number','part_name','revision_record'] as $i => $field) {
                    $i === 0 ? $q->where($field, 'like', "%{$search}%") : $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        foreach (['customer','drawing_status','business_category'] as $field) {
            if ($request->filled($field)) $query->where($field, $request->query($field));
        }

        $rows = $query->with('customCells')->orderBy('row_order')->orderBy('id')->paginate(50)->withQueryString();
        $customColumns = DocumentControlColumn::orderBy('display_order')->orderBy('id')->get();
        $options = [];
        foreach (['customer','drawing_status','business_category'] as $field) {
            $options[$field] = DocumentControlRegistration::whereNotNull($field)->distinct()->orderBy($field)->pluck($field);
        }
        $formOptions = [
            'drawing_type' => DocumentControlRegistration::whereNotNull('drawing_type')->distinct()->pluck('drawing_type')
                ->merge(['Drawing Sub, Assy', 'Drawing Component', 'Drawing Customer'])->filter()->unique()->sort()->values(),
            'customer' => Customer::orderBy('name')->pluck('name')->filter()->unique()->values(),
            'drawing_status' => DocumentControlRegistration::whereNotNull('drawing_status')->distinct()->pluck('drawing_status')
                ->merge(['New Drawing', 'Revisi Drawing'])->filter()->unique()->sort()->values(),
            'business_category' => BusinessCategory::orderBy('name')->pluck('name')->filter()->unique()->values(),
        ];
        $workflowTask = $request->filled('workflow_task')
            ? ProjectWorkflowTask::with(['project.product','revision'])->where('stage',ProjectWorkflowTask::STAGE_DRAWING)->findOrFail($request->integer('workflow_task'))
            : null;
        $editRegistration=$request->filled('edit') ? DocumentControlRegistration::findOrFail($request->integer('edit')) : null;
        return view('document-control.index', compact('rows', 'options', 'formOptions', 'customColumns', 'workflowTask', 'editRegistration'));
    }

    public function store(Request $request)
    {
        $workflowTask = $request->filled('workflow_task_id')
            ? ProjectWorkflowTask::where('stage',ProjectWorkflowTask::STAGE_DRAWING)->findOrFail($request->integer('workflow_task_id'))
            : null;
        $payload=$this->validated($request);
        if(!$workflowTask && $request->filled('manual_project_id')){
            $project=DocumentProject::with(['product','revisions'=>fn($q)=>$q->latest('version_number')])
                ->whereHas('workflowTasks',fn($q)=>$q->where('stage',ProjectWorkflowTask::STAGE_BREAKDOWN)->where('metadata->source','manual_breakdown'))
                ->findOrFail($request->integer('manual_project_id'));
            $activeCategory=BusinessCategoryContext::selected();
            abort_if($activeCategory && $project->product?->code!==$activeCategory->code,422,'Project tidak berada pada Business Category aktif.');
            $revision=$project->revisions->firstOrFail();
            $workflowTask=ProjectWorkflowTask::firstOrCreate([
                'document_revision_id'=>$revision->id,
                'stage'=>ProjectWorkflowTask::STAGE_DRAWING,
            ],[
                'document_project_id'=>$project->id,'assigned_role'=>'document_control',
                'status'=>ProjectWorkflowTask::STATUS_PENDING,'available_at'=>now(),
                'metadata'=>['source'=>'manual_breakdown_registration','without_a00'=>true],
            ]);
            $payload['a00']=$revision->a00==='ada'?'ada':'tidak ada';
        }
        if (!$workflowTask && $request->boolean('create_manual_project')) {
            $activeCategory=BusinessCategoryContext::selected();
            abort_if($activeCategory && strcasecmp(trim((string)$payload['business_category']),$activeCategory->name)!==0,422,'Business Category form harus sama dengan kategori aktif.');
            $request->validate([
                'customer' => ['required','string','max:500'],
                'project' => ['required','string','max:500'],
                'part_number' => ['required','string','max:500'],
                'part_name' => ['required','string','max:500'],
                'business_category' => ['required','string','max:500'],
            ]);
            $workflowTask = DB::transaction(function () use ($payload, $request) {
                $categoryName = trim((string) $payload['business_category']);
                $product = Product::firstOrCreate(
                    ['name' => $categoryName],
                    ['code' => strtoupper(Str::slug($categoryName, '-')), 'line' => '']
                );
                $projectKey = hash('sha256', mb_strtolower(implode('|', [
                    trim((string) $payload['customer']), trim((string) $payload['project']),
                    trim((string) $payload['part_number']), trim((string) $payload['part_name']),
                ])));
                $project = DocumentProject::firstOrCreate(['project_key' => $projectKey], [
                    'product_id' => $product->id, 'customer' => $payload['customer'],
                    'model' => $payload['project'], 'part_number' => $payload['part_number'],
                    'part_name' => $payload['part_name'],
                ]);
                $version = ((int) $project->revisions()->max('version_number')) + 1;
                $revision = DocumentRevision::create([
                    'document_project_id' => $project->id, 'version_number' => $version,
                    'received_date' => $payload['registration_date'], 'pic_engineering' => $request->user()->name,
                    'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT, 'a00' => 'tidak ada',
                    'partlist_original_name' => '', 'partlist_file_path' => '',
                    'umh_original_name' => '', 'umh_file_path' => '',
                    'change_remark' => 'Registrasi Drawing manual tanpa proses A00.',
                ]);
                return ProjectWorkflowTask::create([
                    'document_project_id' => $project->id, 'document_revision_id' => $revision->id,
                    'stage' => ProjectWorkflowTask::STAGE_DRAWING, 'assigned_role' => 'document_control',
                    'status' => ProjectWorkflowTask::STATUS_PENDING, 'available_at' => now(),
                    'metadata' => ['source' => 'manual_drawing_registration', 'without_a00' => true],
                ]);
            });
            $payload['a00'] = 'tidak ada';
        }
        if($workflowTask){
            $registration=DocumentControlRegistration::firstOrNew(['workflow_task_id'=>$workflowTask->id]);
            $isNew=!$registration->exists;
            $registration->fill($payload+[
                'document_project_id'=>$workflowTask->document_project_id,
                'document_revision_id'=>$workflowTask->document_revision_id,
            ]);
            if($isNew){
                $registration->created_by=$request->user()->id;
                $registration->row_order=((int)DocumentControlRegistration::max('row_order'))+1000;
            }
            $registration->save();
        }else{
            $registration=DocumentControlRegistration::create($payload+[
                'created_by'=>$request->user()->id,
                'row_order'=>((int)DocumentControlRegistration::max('row_order'))+1000,
            ]);
        }
        if($workflowTask && $workflowTask->status===ProjectWorkflowTask::STATUS_PENDING) $workflowTask->update(['status'=>ProjectWorkflowTask::STATUS_IN_PROGRESS,'assigned_user_id'=>$request->user()->id,'started_at'=>now()]);
        if ($workflowTask && $request->boolean('complete_distribution')) {
            return $this->completeDistribution($request, $workflowTask->fresh());
        }
        $message = $workflowTask ? 'Registrasi drawing berhasil disimpan.' : 'Registrasi document control berhasil ditambahkan.';
        if ($request->expectsJson()) return response()->json(['message' => $message]);
        return back()->with('success', $message);
    }

    public function update(Request $request, DocumentControlRegistration $registration)
    {
        $registration->update($this->validated($request));
        if ($request->expectsJson()) return response()->json(['message' => 'Registrasi berhasil diperbarui.']);
        return back()->with('success', 'Registrasi berhasil diperbarui.');
    }

    public function completeDistribution(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_DRAWING && $task->assigned_role === 'document_control', 404);

        $registration = DocumentControlRegistration::where('workflow_task_id', $task->id)->first();
        if (!$registration) {
            return back()->with('error', 'Registrasi drawing harus disimpan sebelum distribusi diselesaikan.');
        }

        $required = [
            'registration_no' => 'nomor registrasi',
            'registration_date' => 'tanggal registrasi',
        ];
        $missing = collect($required)->filter(fn ($label, $field) => blank($registration->{$field}))->values();
        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Lengkapi '. $missing->join(', ', ' dan ') .' sebelum menyelesaikan distribusi.');
        }
        if (!$registration->hasAnyDistribution()) {
            return back()->with('error', 'Isi minimal satu tanggal distribusi sebelum menyelesaikan proses drawing.');
        }

        $pendingDistributions = $registration->missingDistributionLabels();

        DB::transaction(function () use ($request, $task, $registration, $pendingDistributions) {
            $task->lockForUpdate()->find($task->id);
            if ($task->status !== ProjectWorkflowTask::STATUS_COMPLETED) {
                $task->update([
                    'status' => ProjectWorkflowTask::STATUS_COMPLETED,
                    'assigned_user_id' => $task->assigned_user_id ?: $request->user()->id,
                    'started_at' => $task->started_at ?: now(),
                    'completed_by_id' => $request->user()->id,
                    'completed_at' => now(),
                ]);
            }

            ProjectWorkflowTask::firstOrCreate([
                'document_revision_id' => $task->document_revision_id,
                'stage' => ProjectWorkflowTask::STAGE_BREAKDOWN,
            ], [
                'document_project_id' => $task->document_project_id,
                'assigned_role' => 'admin_costing',
                'status' => ProjectWorkflowTask::STATUS_PENDING,
                'available_at' => now(),
                'metadata' => [
                    'source' => 'drawing_distribution',
                    'drawing_task_id' => $task->id,
                    'registration_id' => $registration->id,
                    'registration_no' => $registration->registration_no,
                    'pending_distributions' => $pendingDistributions,
                ],
            ]);
        });

        $message = 'Distribusi drawing selesai dan task Breakdown telah dikirim ke Admin Costing.';
        if ($pendingDistributions !== []) {
            $message .= ' Belum didistribusikan ke: '.implode(', ', $pendingDistributions).'.';
        }
        return redirect()->route('document-control.inbox')->with('success', $message);
    }

    public function skipDrawing(Request $request, DocumentRevision $revision)
    {
        $data = $request->validate([
            'drawing_skip_reason' => ['required', 'string', 'max:1000'],
        ], [
            'drawing_skip_reason.required' => 'Alasan project tidak memiliki drawing wajib diisi.',
        ]);
        $revision->loadMissing('project');

        DB::transaction(function () use ($request, $revision, $data) {
            $drawingTask = ProjectWorkflowTask::firstOrCreate([
                'document_revision_id' => $revision->id,
                'stage' => ProjectWorkflowTask::STAGE_DRAWING,
            ], [
                'document_project_id' => $revision->document_project_id,
                'assigned_role' => 'document_control',
                'available_at' => now(),
            ]);

            abort_if($drawingTask->drawingRegistration()->exists(), 422, 'Project sudah memiliki registrasi drawing.');

            $drawingTask->update([
                'status' => ProjectWorkflowTask::STATUS_COMPLETED,
                'assigned_user_id' => $drawingTask->assigned_user_id ?: $request->user()->id,
                'started_at' => $drawingTask->started_at ?: now(),
                'completed_by_id' => $request->user()->id,
                'completed_at' => now(),
                'notes' => 'Tidak ada drawing: '.$data['drawing_skip_reason'],
                'metadata' => array_merge($drawingTask->metadata ?? [], [
                    'drawing_unavailable' => true,
                    'drawing_skipped_by' => $request->user()->name,
                    'drawing_skipped_at' => now()->toIso8601String(),
                    'drawing_skip_reason' => $data['drawing_skip_reason'],
                ]),
            ]);

            ProjectWorkflowTask::firstOrCreate([
                'document_revision_id' => $revision->id,
                'stage' => ProjectWorkflowTask::STAGE_BREAKDOWN,
            ], [
                'document_project_id' => $revision->document_project_id,
                'assigned_role' => 'admin_costing',
                'status' => ProjectWorkflowTask::STATUS_PENDING,
                'available_at' => now(),
                'metadata' => ['source' => 'drawing_skipped', 'drawing_task_id' => $drawingTask->id],
            ]);
        });

        return redirect()->route('document-control.inbox')
            ->with('success', ($revision->project?->part_number ?: 'Project').' ditandai tidak memiliki drawing. Proses distribusi drawing dilewati.');
    }

    public function destroy(Request $request, DocumentControlRegistration $registration)
    {
        $registration->delete();
        if ($request->expectsJson()) return response()->json(['message' => 'Baris dihapus.']);
        return back()->with('success', 'Registrasi berhasil dihapus.');
    }

    public function updateCell(Request $request, DocumentControlRegistration $registration)
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:'.implode(',', self::FIELDS)],
            'value' => ['nullable', 'string', 'max:500'],
        ]);

        $value = trim((string) ($validated['value'] ?? ''));
        if (in_array($validated['field'], self::DATE_FIELDS, true)) {
            if ($value === '') {
                $value = null;
            } else {
                try {
                    $value = Carbon::parse($value)->format('Y-m-d');
                } catch (\Throwable) {
                    return response()->json(['message' => 'Format tanggal tidak valid.'], 422);
                }
            }
        } elseif ($value === '') {
            $value = null;
        }

        $registration->update([$validated['field'] => $value]);

        return response()->json([
            'message' => 'Tersimpan',
            'value' => in_array($validated['field'], self::DATE_FIELDS, true) && $value
                ? Carbon::parse($value)->format('d-M-y')
                : $value,
        ]);
    }

    public function insertRow(Request $request)
    {
        $validated = $request->validate([
            'reference_id' => ['nullable', 'integer', 'exists:document_control_registrations,id'],
            'position' => ['required', 'in:above,below,end'],
        ]);
        return \DB::transaction(function () use ($validated, $request) {
            if ($validated['position'] === 'end' || empty($validated['reference_id'])) {
                $order = ((int) DocumentControlRegistration::max('row_order')) + 1000;
            } else {
                $reference = DocumentControlRegistration::findOrFail($validated['reference_id']);
                $order = (int) $reference->row_order + ($validated['position'] === 'below' ? 1 : 0);
                DocumentControlRegistration::where('row_order', '>=', $order)->increment('row_order');
            }
            $row = DocumentControlRegistration::create(['row_order' => $order, 'created_by' => $request->user()->id]);
            return response()->json(['message' => 'Baris ditambahkan', 'id' => $row->id]);
        });
    }

    public function updateCustomCell(Request $request, DocumentControlRegistration $registration)
    {
        $validated = $request->validate(['column_id' => ['required','exists:document_control_columns,id'], 'value' => ['nullable','string','max:5000']]);
        $cell = DocumentControlCustomCell::updateOrCreate(
            ['registration_id' => $registration->id, 'column_id' => $validated['column_id']],
            ['value' => $validated['value'] ?? null]
        );
        return response()->json(['message' => 'Tersimpan', 'value' => $cell->value]);
    }

    public function storeColumn(Request $request)
    {
        $validated = $request->validate(['name' => ['required','string','max:100'], 'width' => ['nullable','integer','min:60','max:500']]);
        $column = DocumentControlColumn::create(['name' => $validated['name'], 'width' => $validated['width'] ?? 140, 'display_order' => ((int) DocumentControlColumn::max('display_order')) + 1]);
        return response()->json(['message' => 'Kolom ditambahkan', 'column' => $column]);
    }

    public function updateColumn(Request $request, DocumentControlColumn $column)
    {
        $column->update($request->validate(['name' => ['required','string','max:100'], 'width' => ['nullable','integer','min:60','max:500']]));
        return response()->json(['message' => 'Kolom diperbarui']);
    }

    public function destroyColumn(DocumentControlColumn $column)
    {
        $column->delete();
        return response()->json(['message' => 'Kolom dihapus']);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required','file','mimes:xlsx,xls','max:20480']]);
        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $inserted = 0;
        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $values = [];
            foreach (range('B', 'Z') as $column) $values[] = $sheet->getCell($column.$row)->getCalculatedValue();
            foreach (['AA','AB','AC','AD','AE'] as $column) $values[] = $sheet->getCell($column.$row)->getCalculatedValue();
            if (!array_filter($values, fn ($value) => $value !== null && $value !== '')) continue;
            $data = array_combine(self::FIELDS, $values);
            foreach (self::DATE_FIELDS as $field) $data[$field] = $this->excelDate($data[$field]);
            $registration = DocumentControlRegistration::updateOrCreate(
                ['registration_no' => $data['registration_no'], 'part_number' => $data['part_number'], 'revision_number' => $data['revision_number']],
                $data + ['created_by' => $request->user()->id]
            );
            if ($registration->wasRecentlyCreated) {
                $registration->update(['row_order' => ((int) DocumentControlRegistration::where('id', '!=', $registration->id)->max('row_order')) + 1000]);
            }
            $inserted++;
        }
        return back()->with('success', "Impor selesai: {$inserted} baris diproses.");
    }

    private function validated(Request $request): array
    {
        $rules = array_fill_keys(self::FIELDS, ['nullable','string','max:500']);
        foreach (self::DATE_FIELDS as $field) $rules[$field] = ['nullable','date'];
        $rules['registration_no'] = ['required','string','max:100'];
        return $request->validate($rules);
    }

    private function excelDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '#N/A') return null;
        try {
            if (is_numeric($value)) return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) { return null; }
    }
}
