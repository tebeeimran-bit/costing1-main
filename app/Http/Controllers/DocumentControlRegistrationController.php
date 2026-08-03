<?php

namespace App\Http\Controllers;

use App\Models\DocumentControlRegistration;
use App\Models\DocumentControlColumn;
use App\Models\DocumentControlCustomCell;
use App\Models\ProjectWorkflowTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $workflowTask = $request->filled('workflow_task')
            ? ProjectWorkflowTask::with(['project.product','revision'])->where('stage',ProjectWorkflowTask::STAGE_DRAWING)->findOrFail($request->integer('workflow_task'))
            : null;
        $editRegistration=$request->filled('edit') ? DocumentControlRegistration::findOrFail($request->integer('edit')) : null;
        return view('document-control.index', compact('rows', 'options', 'customColumns', 'workflowTask', 'editRegistration'));
    }

    public function store(Request $request)
    {
        $workflowTask = $request->filled('workflow_task_id')
            ? ProjectWorkflowTask::where('stage',ProjectWorkflowTask::STAGE_DRAWING)->findOrFail($request->integer('workflow_task_id'))
            : null;
        $payload=$this->validated($request);
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
            DocumentControlRegistration::create($payload+[
                'created_by'=>$request->user()->id,
                'row_order'=>((int)DocumentControlRegistration::max('row_order'))+1000,
            ]);
        }
        if($workflowTask && $workflowTask->status===ProjectWorkflowTask::STATUS_PENDING) $workflowTask->update(['status'=>ProjectWorkflowTask::STATUS_IN_PROGRESS,'assigned_user_id'=>$request->user()->id,'started_at'=>now()]);
        return back()->with('success', $workflowTask ? 'Registrasi drawing berhasil disimpan.' : 'Registrasi document control berhasil ditambahkan.');
    }

    public function update(Request $request, DocumentControlRegistration $registration)
    {
        $registration->update($this->validated($request));
        return back()->with('success', 'Registrasi berhasil diperbarui.');
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
