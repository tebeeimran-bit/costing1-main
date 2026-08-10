<?php

namespace App\Http\Controllers;

use App\Models\CostingExcelTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CostingExcelTemplateController extends Controller
{
    public function index(Request $request)
    {
        $activeType = array_key_exists((string) $request->query('type'), CostingExcelTemplate::TYPES)
            ? (string) $request->query('type')
            : 'costing';

        return view('database.costing-excel-templates', [
            'activeType' => $activeType,
            'templateTypes' => CostingExcelTemplate::TYPES,
            'templates' => CostingExcelTemplate::with('uploader')
                ->where('template_type', $activeType)
                ->orderBy('assy_count')
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'template_type' => ['required', 'in:'.implode(',', array_keys(CostingExcelTemplate::TYPES))],
            'assy_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'template_file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $templateType = $data['template_type'];
        $assyCount = $templateType === 'costing' ? (int) ($data['assy_count'] ?? 0) : 1;
        if ($templateType === 'costing' && $assyCount < 1) {
            return back()->withErrors(['assy_count' => 'Jumlah assy wajib diisi untuk Template Costing.'])->withInput();
        }

        $file = $data['template_file'];
        try {
            IOFactory::createReaderForFile($file->getRealPath())->load($file->getRealPath());
        } catch (\Throwable) {
            return back()->withErrors(['template_file' => 'File tidak dapat dibaca sebagai workbook Excel yang valid.'])->withInput();
        }

        $path = $file->storeAs(
            'templates/'.$templateType,
            $templateType.'-'.$assyCount.'-'.Str::uuid().'.xlsx',
            'local'
        );
        $existing = CostingExcelTemplate::where('template_type', $templateType)
            ->where('assy_count', $assyCount)
            ->first();
        if ($existing?->file_path) {
            Storage::disk('local')->delete($existing->file_path);
        }
        CostingExcelTemplate::updateOrCreate([
            'template_type' => $templateType,
            'assy_count' => $assyCount,
        ], [
            'name' => $data['name'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'is_active' => true,
            'uploaded_by' => $request->user()->id,
        ]);

        $label = CostingExcelTemplate::TYPES[$templateType];
        $detail = $templateType === 'costing' ? ' untuk '.$assyCount.' assy' : '';

        return redirect()->route('database.costing-excel-templates.index', ['type' => $templateType])
            ->with('success', $label.$detail.' berhasil disimpan dan diaktifkan.');
    }

    public function download(CostingExcelTemplate $template)
    {
        abort_unless(Storage::disk('local')->exists($template->file_path), 404, 'File template tidak ditemukan.');

        return Storage::disk('local')->download($template->file_path, $template->original_name);
    }

    public function destroy(CostingExcelTemplate $template)
    {
        if ($template->file_path) {
            Storage::disk('local')->delete($template->file_path);
        }
        $template->delete();

        return redirect()->route('database.costing-excel-templates.index', ['type' => $template->template_type])
            ->with('success', (CostingExcelTemplate::TYPES[$template->template_type] ?? 'Template Excel').' berhasil dihapus.');
    }
}
