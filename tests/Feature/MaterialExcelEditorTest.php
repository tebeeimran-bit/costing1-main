<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentRevision;
use App\Models\Pic;
use App\Models\Plant;
use App\Models\ProjectA00Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MaterialExcelEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_rows_can_be_exported_to_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('costing.material-excel.export'), [
            'materials_json' => json_encode([[
                '__row_no' => 1,
                'part_no' => 'AVSS 0.5 W-B',
                'id_code' => '1116-005WB',
                'part_name' => 'WIRE',
                'qty_req' => '3.500',
                'unit' => 'PCS',
                'pro_code' => 'CUTTING',
                'amount1' => 100,
                'unit_price_basis' => 100,
                'currency' => 'IDR',
                'qty_moq' => 0,
                'cn_type' => 'N',
                'supplier' => 'Supplier A',
                'import_tax' => 0,
            ]]),
            'cycle_times_json' => json_encode([
                ['process' => 'Cutting', 'qty' => 232, 'time_hour' => 0.18839, 'time_sec' => 678, 'time_sec_per_qty' => 3, 'cost_per_sec' => 10.33, 'cost_per_unit' => 7006],
                ['process' => 'Crimping', 'qty' => 439, 'time_hour' => 0.31308, 'time_sec' => 1127, 'time_sec_per_qty' => 3, 'cost_per_sec' => 10.33, 'cost_per_unit' => 11643],
            ]),
            'assy_no' => 'W40294',
            'assy_name' => 'WIRING HARNESS',
            'customer' => 'Astra',
            'customer_code' => 'SMSG',
            'model' => 'K4MA',
            'forecast' => 500,
            'project_period' => 2,
            'plant' => '1501 - Cikarang',
            'rate_usd' => 17923,
            'rate_jpy' => 111.59,
            'rate_idr' => 1,
            'rate_lme' => 13574,
            'rate_period' => '2026-07-01',
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('cogm. W40294 - SMSG.xlsx', (string) $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($path);
        $this->assertStringStartsWith('PK', (string) file_get_contents($path, false, null, 0, 2));

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setLoadSheetsOnly(['Material Cost', 'Lembar1', 'UMH ', 'Resume']);
        $workbook = $reader->load($path);
        $materialCost = $workbook->getSheetByName('Material Cost');
        $this->assertSame('W40294', $materialCost->getCell('F5')->getValue());
        $this->assertSame('NEW', $materialCost->getCell('F11')->getValue());
        $this->assertSame(1, $materialCost->getCell('C18')->getValue());
        $this->assertSame('AVSS 0.5 W-B', $materialCost->getCell('D18')->getValue());
        $this->assertSame(3500, $materialCost->getCell('I18')->getValue());
        $this->assertSame(17923.0, $materialCost->getCell('N8')->getValue());
        $this->assertStringStartsWith('=IF(B18=', $materialCost->getCell('A18')->getValue());
        $this->assertStringStartsWith('=IFERROR(IF(D18=', $materialCost->getCell('B18')->getValue());
        foreach (['L' => 'J', 'M' => 'K', 'N' => 'L', 'O' => 'M', 'P' => 'N', 'Q' => 'O', 'R' => 'P'] as $column => $headerColumn) {
            $this->assertSame("=VLOOKUP(\$D18,Lembar1!\$B:\$P,Lembar1!{$headerColumn}\$1,0)", $materialCost->getCell("{$column}18")->getValue());
        }
        $this->assertSame('AVSS 0.5 W-B', $workbook->getSheetByName('Lembar1')->getCell('B3')->getValue());
        foreach (range('J', 'P') as $column) {
            $this->assertNull($workbook->getSheetByName('Lembar1')->getCell($column.'3')->getValue());
        }
        $umh = $workbook->getSheetByName('UMH ');
        $this->assertSame('Cutting', $umh->getCell('B9')->getValue());
        $this->assertSame(232.0, $umh->getCell('E9')->getValue());
        $this->assertSame('=SUM(F9:F10)', $umh->getCell('F11')->getValue());
        $this->assertSame('=SUM(G9:G10)', $umh->getCell('G11')->getValue());
        $this->assertSame('=SUM(J9:J10)', $umh->getCell('J11')->getValue());
        $this->assertSame("='COGM'!H12", $workbook->getSheetByName('Resume')->getCell('H44')->getValue());
        foreach ($workbook->getDefinedNames() as $definedName) {
            $this->assertStringStartsWith('_xlnm.', $definedName->getName());
        }
    }

    public function test_edited_material_excel_can_be_validated_and_parsed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Cost');
        $sheet->setCellValue('C18', 1);
        foreach (['D' => 'AVSS 0.5 W-B', 'F' => '1116-005WB', 'G' => 'WIRE', 'I' => 552, 'J' => 'PCS', 'K' => 'CUTTING', 'L' => 100, 'M' => 100, 'N' => 'IDR', 'O' => 0, 'P' => 'N', 'Q' => 'Supplier A', 'R' => 0] as $column => $value) {
            $sheet->setCellValue("{$column}18", $value);
        }
        $path = tempnam(sys_get_temp_dir(), 'material-editor-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $response = $this->actingAs($admin)->post(route('costing.material-excel.import'), [
                'material_file' => new UploadedFile($path, 'material-edit.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('rows.0.__row_no', 1)
                ->assertJsonPath('rows.0.qty_req', '552')
                ->assertJsonPath('rows.0.currency', 'IDR');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_cogm_export_freezes_material_columns_and_removes_lookup_sheet(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $response=$this->actingAs($admin)->postJson(route('costing.material-excel.export'),[
            'export_mode'=>'cogm','materials_json'=>json_encode([
                ['__row_no'=>1,'part_no'=>'AVSS 0.5 W-B','id_code'=>'1116-005WB','part_name'=>'WIRE','qty_req'=>10,'unit'=>'MM','pro_code'=>'CUTTING','amount1'=>'1.138,15','unit_price_basis'=>'MTR','currency'=>'IDR','qty_moq'=>12,'cn_type'=>'C','supplier'=>'EWINDO/JLAP/W','import_tax'=>5],
                ['__row_no'=>2,'part_no'=>'avss 0.5 w-b','id_code'=>'OTHER','part_name'=>'DUPLICATE','qty_req'=>20,'unit'=>'PCS','pro_code'=>'ASSEMBLING','amount1'=>'999,99','unit_price_basis'=>'PCE','currency'=>'USD','qty_moq'=>99,'cn_type'=>'E','supplier'=>'Supplier Berbeda','import_tax'=>8],
            ]),
            'cycle_times_json'=>'[]','assy_no'=>'W40294','customer_code'=>'SMSG','rate_idr'=>1,
        ]);
        $response->assertOk();
        $this->assertStringContainsString('COGM W40294 - SMSG.xlsx',(string)$response->headers->get('Content-Disposition'));
        $path=$response->baseResponse->getFile()->getPathname();
        $workbook=(new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($path);
        $this->assertNull($workbook->getSheetByName('Lembar1'));
        $sheet=$workbook->getSheetByName('Material Cost');
        foreach(range('L','R') as $column)$this->assertFalse($sheet->getCell($column.'18')->isFormula(),$column.'18 harus berupa nilai, bukan formula.');
        $this->assertSame(1138.15, $sheet->getCell('L18')->getValue());
        $this->assertSame('MTR', $sheet->getCell('M18')->getValue());
        $this->assertSame('IDR', $sheet->getCell('N18')->getValue());
        $this->assertSame(12.0, $sheet->getCell('O18')->getValue());
        $this->assertSame('C', $sheet->getCell('P18')->getValue());
        $this->assertSame('EWINDO/JLAP/W', $sheet->getCell('Q18')->getValue());
        $this->assertSame(5.0, $sheet->getCell('R18')->getValue());
        foreach (range('L', 'R') as $column) {
            $this->assertSame(
                $sheet->getCell($column.'18')->getValue(),
                $sheet->getCell($column.'19')->getValue(),
                $column.'19 harus mengikuti nilai kemunculan pertama part number yang sama.'
            );
        }
    }

    public function test_empty_edited_excel_cells_remain_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Cost');
        $sheet->setCellValue('C18', 1);
        $sheet->setCellValue('D18', '7408-9000 L');
        $sheet->setCellValue('F18', '1618-10013');
        $sheet->setCellValue('G18', 'ADHESIVE TAPE');
        $sheet->setCellValue('I18', 100);
        $sheet->setCellValue('J18', 'MM');
        $sheet->setCellValue('K18', 'ASSEMBLING');
        $path = tempnam(sys_get_temp_dir(), 'material-editor-empty-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $response = $this->actingAs($admin)->post(route('costing.material-excel.import'), [
                'material_file' => new UploadedFile($path, 'material-edit-empty.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ]);

            $response->assertOk()
                ->assertJsonPath('rows.0.qty_req', '100')
                ->assertJsonPath('rows.0.amount1', '')
                ->assertJsonPath('rows.0.unit_price_basis', '')
                ->assertJsonPath('rows.0.currency', '')
                ->assertJsonPath('rows.0.qty_moq', '')
                ->assertJsonPath('rows.0.cn_type', '')
                ->assertJsonPath('rows.0.supplier', '')
                ->assertJsonPath('rows.0.import_tax', '');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_group_a00_import_updates_every_assy_sheet_from_any_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = BusinessCategory::create(['code' => 'WH', 'name' => 'Wiring Harness']);
        $customer = Customer::create(['code' => 'TEST', 'name' => 'Test Customer']);
        $plant = Plant::create(['code' => 'CKR', 'name' => 'Cikarang']);
        Pic::create(['name' => 'Engineer Test', 'type' => 'engineering']);
        Pic::create(['name' => 'Marketing Test', 'type' => 'marketing']);

        $this->actingAs($admin)->post(route('control-project.a00.store'), [
            'business_category_id' => $category->id, 'customer_id' => $customer->id,
            'plant_id' => $plant->id, 'period' => '2026-08',
            'pic_engineering' => 'Engineer Test', 'pic_marketing' => 'Marketing Test',
            'document_number' => 'A00-GROUP-IMPORT', 'document_date' => '2026-08-10',
            'revision' => '00', 'from_department' => 'MKT', 'to_department' => 'TEAM PROJECT',
            'quantity_uom' => 'Pcs', 'quantity_basis' => 'per Year', 'issue_location' => 'Cikarang',
            'items' => [
                ['model' => 'K4MA', 'assy_name' => 'ASSY ONE', 'assy_number' => 'ASSY-01', 'quantity' => 100, 'quantity_uom' => 'Pcs', 'quantity_basis' => 'per Year'],
                ['model' => 'K4MA', 'assy_name' => 'ASSY TWO', 'assy_number' => 'ASSY-02', 'quantity' => 200, 'quantity_uom' => 'Pcs', 'quantity_basis' => 'per Year'],
            ],
        ])->assertRedirect();

        $form = ProjectA00Form::with('items')->firstOrFail();
        $firstRevisionId = (int) $form->items->firstWhere('assy_number', 'ASSY-01')->document_revision_id;
        $spreadsheet = new Spreadsheet();
        $firstSheet = $spreadsheet->getActiveSheet();
        $firstSheet->setTitle('ASSY-01');
        $secondSheet = $spreadsheet->createSheet();
        $secondSheet->setTitle('ASSY-02');
        foreach ([[$firstSheet, 'SUPPLIER-ONE'], [$secondSheet, 'SUPPLIER-TWO']] as [$sheet, $supplier]) {
            $sheet->setCellValue('C18', 1);
            foreach (['D' => 'PART-'.$supplier, 'F' => 'CODE', 'G' => 'WIRE', 'I' => 10, 'J' => 'PCS', 'K' => 'CUTTING', 'L' => 99, 'M' => 'PCE', 'N' => 'IDR', 'O' => 1, 'P' => 'N', 'Q' => $supplier, 'R' => 0] as $column => $value) {
                $sheet->setCellValue("{$column}18", $value);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'a00-group-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $response = $this->actingAs($admin)->post(route('costing.material-excel.import'), [
                'tracking_revision_id' => $firstRevisionId,
                'material_file' => new UploadedFile($path, 'a00-group.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ]);
            $response->assertOk()->assertJsonPath('group_imported', true)->assertJsonCount(2, 'group_updates');
            $this->assertDatabaseHas('material_breakdowns', ['supplier' => 'SUPPLIER-ONE']);
            $this->assertDatabaseHas('material_breakdowns', ['supplier' => 'SUPPLIER-TWO']);
            $this->assertSame(2, DocumentRevision::whereNotNull('costing_edit_file_path')->count());

            $secondRevisionId = (int) $form->items->firstWhere('assy_number', 'ASSY-02')->document_revision_id;
            CostingData::where('tracking_revision_id', $firstRevisionId)->delete();
            DocumentRevision::findOrFail($secondRevisionId)->update([
                'status' => DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            ]);
            $this->actingAs($admin)->get(route('costing.inbox', ['status' => 'active']))
                ->assertOk()
                ->assertSee('ASSY-01')
                ->assertSee('ASSY-02');
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}
