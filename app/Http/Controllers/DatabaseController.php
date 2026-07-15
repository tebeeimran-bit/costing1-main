<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\Pic;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Wire;
use App\Models\WireRate;
use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\CycleTimeTemplate;
use App\Models\DocumentRevision;
use App\Http\Requests\Database\DestroyPartsBulkRequest;
use App\Http\Requests\Database\ImportPartsExcelRequest;
use App\Http\Requests\Database\ImportWiresExcelRequest;
use App\Http\Requests\Database\StoreBusinessCategoryRequest;
use App\Http\Requests\Database\StoreCustomerRequest;
use App\Http\Requests\Database\StoreCycleTimeTemplateRequest;
use App\Http\Requests\Database\StoreMaterialRequest;
use App\Http\Requests\Database\StorePicRequest;
use App\Http\Requests\Database\StorePlantRequest;
use App\Http\Requests\Database\StoreWireRateRequest;
use App\Http\Requests\Database\StoreWireRequest;
use App\Http\Requests\Database\SwitchWireRateRequest;
use App\Http\Requests\Database\UpdateBusinessCategoryRequest;
use App\Http\Requests\Database\UpdateCustomerRequest;
use App\Http\Requests\Database\UpdateCycleTimeTemplateRequest;
use App\Http\Requests\Database\UpdateMaterialRequest;
use App\Http\Requests\Database\UpdatePicRequest;
use App\Http\Requests\Database\UpdatePlantRequest;
use App\Http\Requests\Database\UpdateWireRateRequest;
use App\Http\Requests\Database\UpdateWireRequest;
use App\Http\Requests\Database\UpdateProjectDocumentRequest;
use App\Services\Database\DatabaseCostingService;
use App\Services\Database\DatabaseMasterDataService;
use App\Services\Database\DatabaseMaterialService;
use App\Services\Database\DatabaseProjectDocumentService;
use App\Services\Database\DatabaseSpreadsheetImportService;
use App\Services\Database\DatabaseWireService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DatabaseController extends Controller
{
    public function index()
    {
        return redirect(route('database.parts', absolute: false));
    }

    public function parts(Request $request)
    {
        $keyword = trim((string) $request->input('q', ''));

        $perPage = (int) $request->input('per_page', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }
        if ($perPage > 500) {
            $perPage = 500;
        }

        $materialsQuery = Material::query();

        if ($keyword !== '') {
            $materialsQuery->where(function ($query) use ($keyword) {
                $like = '%' . $keyword . '%';
                $query->where('material_code', 'like', $like)
                    ->orWhere('material_description', 'like', $like)
                    ->orWhere('material_type', 'like', $like)
                    ->orWhere('material_group', 'like', $like)
                    ->orWhere('maker', 'like', $like);
            });
        }

        $materials = $materialsQuery
            ->orderBy('material_code', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('database.parts', compact('materials'));
    }




    public function customers()
    {
        $customers = Customer::all();
        return view('database.customers', compact('customers'));
    }

    public function storeCustomer(StoreCustomerRequest $request, DatabaseMasterDataService $service)
    {
        $service->create(Customer::class, $request->validated(), ['code', 'name']);

        return back()
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    public function updateCustomer(UpdateCustomerRequest $request, $id, DatabaseMasterDataService $service)
    {
        $customer = Customer::findOrFail($id);

        $service->update($customer, $request->validated(), ['code', 'name']);

        return back()
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroyCustomer($id)
    {
        $customer = Customer::findOrFail($id);

        $isUsed = CostingData::where('customer_id', $customer->id)->exists();
        if ($isUsed) {
            return back()
                ->with('warning', 'Customer tidak bisa dihapus karena sudah digunakan pada data costing.');
        }

        $customer->delete();

        return back()
            ->with('success', 'Customer berhasil dihapus.');
    }

    public function cycleTimeTemplates()
    {
        $templates = CycleTimeTemplate::orderBy('id')->get();
        return view('database.cycle-time-templates', compact('templates'));
    }

    public function storeCycleTimeTemplate(StoreCycleTimeTemplateRequest $request, DatabaseMasterDataService $service)
    {
        $service->create(CycleTimeTemplate::class, $request->validated(), ['process']);

        return redirect(route('database.cycle-time-templates', absolute: false))
            ->with('success', 'Process template berhasil ditambahkan!');
    }

    public function updateCycleTimeTemplate(UpdateCycleTimeTemplateRequest $request, $id, DatabaseMasterDataService $service)
    {
        $template = CycleTimeTemplate::findOrFail($id);

        $service->update($template, $request->validated(), ['process']);

        return redirect(route('database.cycle-time-templates', absolute: false))
            ->with('success', 'Process template berhasil diperbarui!');
    }

    public function destroyCycleTimeTemplate($id)
    {
        $template = CycleTimeTemplate::findOrFail($id);
        $template->delete();

        return redirect(route('database.cycle-time-templates', absolute: false))
            ->with('success', 'Process template berhasil dihapus!');
    }

    public function businessCategories()
    {
        $businessCategories = BusinessCategory::orderBy('code')->orderBy('name')->get();
        return view('database.business-categories', compact('businessCategories'));
    }

    public function storeBusinessCategory(StoreBusinessCategoryRequest $request, DatabaseMasterDataService $service)
    {
        $service->create(BusinessCategory::class, $request->validated(), ['code', 'name']);

        return back()->with('success', 'Business Category berhasil ditambahkan.');
    }

    public function updateBusinessCategory(UpdateBusinessCategoryRequest $request, $id, DatabaseMasterDataService $service)
    {
        $businessCategory = BusinessCategory::findOrFail($id);

        $service->update($businessCategory, $request->validated(), ['code', 'name']);

        return back()->with('success', 'Business Category berhasil diperbarui.');
    }

    public function destroyBusinessCategory($id)
    {
        $businessCategory = BusinessCategory::findOrFail($id);
        $businessCategory->delete();

        return back()->with('success', 'Business Category berhasil dihapus.');
    }

    public function plants()
    {
        $plants = Plant::orderBy('code')->orderBy('name')->get();
        return view('database.plants', compact('plants'));
    }

    public function storePlant(StorePlantRequest $request, DatabaseMasterDataService $service)
    {
        $service->create(Plant::class, $request->validated(), ['code', 'name']);

        return back()->with('success', 'Plant berhasil ditambahkan.');
    }

    public function updatePlant(UpdatePlantRequest $request, $id, DatabaseMasterDataService $service)
    {
        $plant = Plant::findOrFail($id);

        $service->update($plant, $request->validated(), ['code', 'name']);

        return back()->with('success', 'Plant berhasil diperbarui.');
    }

    public function destroyPlant($id)
    {
        $plant = Plant::findOrFail($id);
        $plant->delete();

        return back()->with('success', 'Plant berhasil dihapus.');
    }

    public function pics()
    {
        $pics = Pic::orderBy('type')->orderBy('name')->get();
        return view('database.pics', compact('pics'));
    }

    public function wires(DatabaseWireService $service)
    {
        return view('database.wires', $service->getPageData());
    }

    public function switchWireRateMonth(SwitchWireRateRequest $request, DatabaseWireService $service)
    {
        $ok = $service->switchActiveRate((int) $request->validated('rate_id'));

        if (!$ok) {
            return back()->with('error', 'Rate aktif yang dipilih tidak ditemukan.');
        }

        return back()->with('success', 'Rate aktif berhasil diubah. Harga wire telah diperbaharui.');
    }

    public function storeWireRate(StoreWireRateRequest $request, DatabaseWireService $service)
    {
        $service->createRate($request->validated());

        return back()->with('success', 'Rates wire berhasil ditambahkan.');
    }

    public function updateWireRate(UpdateWireRateRequest $request, $id, DatabaseWireService $service)
    {
        $wireRate = WireRate::findOrFail($id);
        $service->updateRate($wireRate, $request->validated());

        return back()->with('success', 'Rates wire berhasil diperbarui.');
    }

    public function destroyWireRate($id, DatabaseWireService $service)
    {
        $wireRate = WireRate::findOrFail($id);
        $service->deleteRate($wireRate);

        return back()->with('success', 'Rates wire berhasil dihapus.');
    }

    public function storeWire(StoreWireRequest $request, DatabaseWireService $service)
    {
        $service->createWire($request->validated());

        return back()->with('success', 'Wire berhasil ditambahkan.');
    }

    public function updateWire(UpdateWireRequest $request, $id, DatabaseWireService $service)
    {
        $wire = Wire::findOrFail($id);
        $service->updateWire($wire, $request->validated());

        return back()->with('success', 'Wire berhasil diperbarui.');
    }

    public function destroyWire($id, DatabaseWireService $service)
    {
        $wire = Wire::findOrFail($id);
        $service->deleteWire($wire);

        return back()->with('success', 'Wire berhasil dihapus.');
    }

    public function storePic(StorePicRequest $request, DatabaseMasterDataService $service)
    {
        $service->create(Pic::class, $request->validated(), ['name']);

        return back()->with('success', 'PIC berhasil ditambahkan.');
    }

    public function updatePic(UpdatePicRequest $request, $id, DatabaseMasterDataService $service)
    {
        $pic = Pic::findOrFail($id);

        $service->update($pic, $request->validated(), ['name']);

        return back()->with('success', 'PIC berhasil diperbarui.');
    }

    public function destroyPic($id)
    {
        $pic = Pic::findOrFail($id);
        $pic->delete();

        return back()->with('success', 'PIC berhasil dihapus.');
    }

    // CRUD for Parts/Materials
    public function createPart()
    {
        return view('database.parts-form', ['material' => null]);
    }

    public function storePart(StoreMaterialRequest $request, DatabaseMaterialService $service)
    {
        $service->create($request->validated());

        $target = route('database.parts', absolute: false);
        session()->flash('success', 'Material berhasil ditambahkan!');

        return response('', 302, ['Location' => $target]);
    }

    public function downloadPartsTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Parts');

        $headers = [
            'plant',
            'material_code',
            'material_description',
            'material_type',
            'material_group',
            'base_uom',
            'price',
            'purchase_unit',
            'currency',
            'moq',
            'cn',
            'maker',
            'add_cost_import_tax',
            'price_update',
            'price_before',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $example = [
            '1501',
            'ABC-123',
            'CONNECTOR SAMPLE',
            'RAW',
            'ELECTRICAL',
            'PCS',
            '12345',
            'PCS',
            'IDR',
            '1000',
            'N',
            'SUPPLIER A',
            '0',
            now()->format('Y-m-d'),
            '12000',
        ];

        foreach ($example as $index => $value) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '2', $value);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'parts_template_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, 'database-parts-template.xlsx')->deleteFileAfterSend(true);
    }

    public function importPartsExcel(ImportPartsExcelRequest $request, DatabaseSpreadsheetImportService $service)
    {
        try {
            $result = $service->importParts($request->file('import_file')->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        return back()->with($result['status'] === 'warning' ? 'warning' : 'success', $result['message']);
    }

    public function downloadWiresTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Wires');

        $headers = [
            'item',
            'machine_maintenance',
            'fix_cost',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $exampleWire = Wire::query()->orderBy('id')->first();
        $example = [
            trim((string) ($exampleWire?->item ?? 'AV 0.3f')),
            trim((string) ($exampleWire?->machine_maintenance ?? '0')),
            (float) ($exampleWire?->fix_cost ?? 0),
        ];

        foreach ($example as $index => $value) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '2', $value);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'wires_template_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, 'database-wires-template.xlsx')->deleteFileAfterSend(true);
    }

    public function importWiresExcel(ImportWiresExcelRequest $request, DatabaseWireService $service)
    {
        try {
            $result = $service->importWires($request->file('import_file')->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        if (($result['status'] ?? '') === 'validation_error') {
            return back()->withErrors($result['errors'], 'importWires');
        }

        $flashType = ($result['status'] ?? '') === 'warning' ? 'warning' : 'success';
        $response = back()->with($flashType, $result['message']);

        if (!empty($result['warning'])) {
            $response = $response->with('warning', $result['warning']);
        }
        if (!empty($result['issues'])) {
            $response = $response->with('wireImportIssues', $result['issues']);
        }

        return $response;
    }

    public function destroyPartsBulk(DestroyPartsBulkRequest $request, DatabaseMaterialService $service)
    {
        $ids = collect($request->validated('material_ids'))->map(fn ($id) => (int) $id)->unique()->values();
        try {
            $deleted = $service->destroyBulk($ids);
        } catch (\Throwable $e) {
            \Log::error('DatabaseController@destroyPartsBulk failed', [
                'ids' => $ids->all(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Hapus massal gagal. Silakan coba lagi.');
        }

        return back()->with('success', 'Hapus massal berhasil. Jumlah data terhapus: ' . $deleted . '.');
    }

    public function destroyPartsAll(Request $request, DatabaseMaterialService $service)
    {
        try {
            $deleted = $service->destroyAll();
        } catch (\Throwable $e) {
            \Log::error('DatabaseController@destroyPartsAll failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Hapus semua data gagal. Silakan coba lagi.');
        }

        return back()->with('success', 'Semua data material berhasil dihapus. Jumlah data terhapus: ' . $deleted . '.');
    }

    public function editPart($id)
    {
        $material = Material::findOrFail($id);
        return view('database.parts-form', compact('material'));
    }

    public function updatePart(UpdateMaterialRequest $request, $id, DatabaseMaterialService $service)
    {
        $material = Material::findOrFail($id);
        $service->update($material, $request->validated());

        $target = route('database.parts', absolute: false);
        session()->flash('success', 'Material berhasil diperbarui!');

        return response('', 302, ['Location' => $target]);
    }

    public function destroyPart(Request $request, $id, DatabaseMaterialService $service)
    {
        $material = Material::findOrFail($id);
        try {
            $service->destroy($material);
        } catch (\Throwable $e) {
            \Log::error('DatabaseController@destroyPart failed', [
                'material_id' => (int) $id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Material gagal dihapus. Silakan coba lagi.');
        }

        $target = route('database.parts', absolute: false);
        session()->flash('success', 'Material berhasil dihapus!');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil dihapus!',
                'redirect' => $target,
            ]);
        }

        return response('', 302, ['Location' => $target]);
    }

    public function projectDocuments(Request $request, DatabaseProjectDocumentService $service)
    {
        return view('database.project-documents', $service->getPageData($request));
    }

    public function updateProjectDocument(UpdateProjectDocumentRequest $request, $id, DatabaseProjectDocumentService $service)
    {
        $revision = DocumentRevision::findOrFail($id);

        $service->update($revision, $request->validated(), $request);

        if ($request->boolean('return_to_dashboard')) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Dokumen project berhasil diperbarui.');
        }

        return back()->with('success', 'Dokumen project berhasil diperbarui.');
    }

    public function destroyProjectDocument($id, DatabaseProjectDocumentService $service)
    {
        $revision = DocumentRevision::findOrFail($id);

        $service->destroy($revision);

        return back()->with('success', 'Dokumen project berhasil dihapus.');
    }
}
