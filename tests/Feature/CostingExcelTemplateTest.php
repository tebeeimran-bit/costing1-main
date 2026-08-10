<?php

namespace Tests\Feature;

use App\Models\CostingExcelTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CostingExcelTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_replace_template_by_assy_count(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('database.costing-excel-templates.index'))
            ->assertOk()
            ->assertSee('Template Excel')
            ->assertSee('Template Costing')
            ->assertSee('Template Partlist')
            ->assertSee('Template UMH')
            ->assertSee('Template A00');

        $this->actingAs($user)->post(route('database.costing-excel-templates.store'), [
            'template_type' => 'costing',
            'assy_count' => 2,
            'name' => 'Template COGM 2 Assy',
            'template_file' => $this->workbookUpload('template-2-assy.xlsx'),
        ])->assertRedirect()->assertSessionHas('success');

        $template = CostingExcelTemplate::firstOrFail();
        $oldPath = $template->file_path;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($user)->post(route('database.costing-excel-templates.store'), [
            'template_type' => 'costing',
            'assy_count' => 2,
            'name' => 'Template COGM 2 Assy Revisi',
            'template_file' => $this->workbookUpload('template-2-assy-revisi.xlsx'),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, CostingExcelTemplate::count());
        $this->assertSame('Template COGM 2 Assy Revisi', $template->fresh()->name);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($template->fresh()->file_path);
    }

    public function test_admin_can_manage_each_template_menu_independently(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'admin']);

        foreach (['partlist', 'umh', 'a00'] as $type) {
            $this->actingAs($user)->post(route('database.costing-excel-templates.store'), [
                'template_type' => $type,
                'name' => 'Template '.strtoupper($type),
                'template_file' => $this->workbookUpload($type.'.xlsx'),
            ])->assertRedirect(route('database.costing-excel-templates.index', ['type' => $type]))
                ->assertSessionHas('success');
        }

        $this->assertDatabaseHas('costing_excel_templates', ['template_type' => 'partlist', 'assy_count' => 1]);
        $this->assertDatabaseHas('costing_excel_templates', ['template_type' => 'umh', 'assy_count' => 1]);
        $this->assertDatabaseHas('costing_excel_templates', ['template_type' => 'a00', 'assy_count' => 1]);

        $this->actingAs($user)
            ->get(route('database.costing-excel-templates.index', ['type' => 'umh']))
            ->assertOk()
            ->assertSee('Upload Template UMH')
            ->assertSee('Template UMH');
    }

    private function workbookUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'costing-template-').'.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Material Cost');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
