<?php

namespace Tests\Feature;

use App\Models\User;
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
        $this->assertSame(1, $materialCost->getCell('C18')->getValue());
        $this->assertSame('AVSS 0.5 W-B', $materialCost->getCell('D18')->getValue());
        $this->assertSame(3500, $materialCost->getCell('I18')->getValue());
        $this->assertSame(17923.0, $materialCost->getCell('N8')->getValue());
        $this->assertSame('AVSS 0.5 W-B', $workbook->getSheetByName('Lembar1')->getCell('B2')->getValue());
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
}
