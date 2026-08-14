<?php

namespace Tests\Feature;

use App\Models\CogmSubmission;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\ProjectDocumentRevision;
use App\Models\User;
use App\Models\UnpricedPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MarketingCogmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function submission(): CogmSubmission
    {
        $product=Product::create(['code'=>'WH','name'=>'Wiring Harness','line'=>'']);
        $project=DocumentProject::create(['product_id'=>$product->id,'customer'=>'Customer','model'=>'K1','part_number'=>'32100-K1-0001','part_name'=>'Harness','project_key'=>hash('sha256','marketing-test')]);
        $revision=DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>now(),'pic_engineering'=>'Engineer','pic_marketing'=>'Marketing User','status'=>DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,'partlist_original_name'=>'','partlist_file_path'=>'','umh_original_name'=>'','umh_file_path'=>'']);
        return CogmSubmission::create(['document_revision_id'=>$revision->id,'submitted_at'=>now(),'pic_marketing'=>'Marketing User','cogm_value'=>100000,'submitted_by'=>'Costing']);
    }

    public function test_marketing_comment_is_recorded_and_notifies_project_team(): void
    {
        Notification::fake();
        $marketing=User::factory()->create(['name'=>'Marketing User','role'=>'marketing']);
        $recipients=collect(['admin','admin_costing','coordinator_costing','engineering'])->map(fn($role)=>User::factory()->create(['role'=>$role]));
        $submission=$this->submission();

        $this->actingAs($marketing)->post(route('marketing.cogm-comments.store',$submission),['comment'=>'Mohon cek ulang COGM.'])->assertRedirect();

        $this->assertDatabaseHas('cogm_submission_comments',['cogm_submission_id'=>$submission->id,'comment'=>'Mohon cek ulang COGM.']);
        $this->assertDatabaseHas('cogm_submission_events',['cogm_submission_id'=>$submission->id,'event_type'=>'comment']);
        Notification::assertSentTo($recipients,\App\Notifications\CostingGroupChanged::class);
    }

    public function test_costing_with_unpriced_parts_can_enter_approval_and_shows_pricing_note_in_marketing(): void
    {
        $admin = User::factory()->create(['name' => 'Costing Admin', 'role' => 'admin']);
        $marketing = User::factory()->create(['name' => 'Marketing User', 'role' => 'marketing']);
        $submission = $this->submission();
        $revision = $submission->revision;
        $revision->update(['status' => DocumentRevision::STATUS_PENDING_PRICING]);
        $customer = Customer::create(['code' => 'TEST-MKT', 'name' => 'Test Marketing Customer']);
        CostingData::create([
            'tracking_revision_id' => $revision->id,
            'product_id' => $revision->project->product_id,
            'customer_id' => $customer->id,
            'period' => '2026-08',
        ]);
        UnpricedPart::create([
            'document_revision_id' => $revision->id,
            'part_number' => 'PART-NO-PRICE',
            'part_name' => 'Part tanpa harga',
        ]);

        $this->actingAs($admin)
            ->post(route('costing-approvals.submit', $revision))
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 part belum memiliki harga'));

        $this->assertSame(DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL, $revision->fresh()->status);

        $revision->update(['status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING]);
        $this->actingAs($marketing)
            ->get(route('marketing.cogm-inbox'))
            ->assertOk()
            ->assertSee('1 part belum memiliki harga');
    }

    public function test_submitted_costing_save_persists_form_without_creating_marketing_update(): void
    {
        $admin = User::factory()->create(['name' => 'Costing Admin', 'role' => 'admin']);
        $submission = $this->submission();
        $revision = $submission->revision;
        $customer = Customer::create(['code' => 'EDIT-MKT', 'name' => 'Edit Marketing Customer']);
        $costing = CostingData::create([
            'tracking_revision_id' => $revision->id,
            'product_id' => $revision->project->product_id,
            'customer_id' => $customer->id,
            'period' => '2026-08',
            'material_cost' => 1065.95,
            'labor_cost' => 545.48,
        ]);

        $this->actingAs($admin)->post(route('costing.store'), [
            'update_section' => 'resume_cogm',
            'edit_submitted' => 1,
            'costing_data_id' => $costing->id,
            'tracking_revision_id' => $revision->id,
            'material_cost' => 1065.95,
            'labor_cost' => 545.48,
            'overhead_cost' => 53.30,
            'scrap_cost' => 0,
            'revenue' => 0,
            'qty_good' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('costing_data', [
            'id' => $costing->id,
            'overhead_cost' => 53.30,
        ]);
        $this->assertDatabaseHas('cogm_submissions', [
            'id' => $submission->id,
            'cogm_value' => 100000,
            'update_count' => 0,
        ]);
        $this->assertDatabaseMissing('cogm_submission_events', [
            'cogm_submission_id' => $submission->id,
            'event_type' => 'cogm_updated',
        ]);
    }

    public function test_manual_cogm_upload_becomes_authoritative_and_updates_summary_values(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['name' => 'Costing Admin', 'role' => 'admin']);
        $submission = $this->submission();
        $revision = $submission->revision;
        $customer = Customer::create(['code' => 'MANUAL', 'name' => 'Manual COGM Customer']);
        CostingData::create([
            'tracking_revision_id' => $revision->id,
            'product_id' => $revision->project->product_id,
            'customer_id' => $customer->id,
            'period' => '2026-08',
        ]);

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([
            ['TOTAL MATERIAL COST', 'Rp', 1065.95],
            ['PROCESS COST', 'Rp', 545.48],
            ['DEPRESIASI TOOLING COST', 'Rp', 53.30],
            ['ADMINISTRATION COST', 'Rp', 10],
            ['COGM', 'Rp', 1674.73],
        ], null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'manual-cogm-').'.xlsx';
        (new Xlsx($book))->save($path);

        $this->actingAs($admin)->post(route('costing.manual-cogm.store', $revision), [
            'cogm_file' => new UploadedFile($path, 'COGM Manual.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            'price_update_number' => 2,
            'pricing_status' => 'full_price',
            'full_price_confirmation' => 1,
            'description' => 'Semua harga supplier sudah final.',
        ])->assertRedirect()->assertSessionHas('success');

        @unlink($path);
        $this->assertDatabaseHas('costing_data', [
            'tracking_revision_id' => $revision->id,
            'material_cost' => 1065.95,
            'labor_cost' => 545.48,
            'overhead_cost' => 53.30,
            'scrap_cost' => 10,
        ]);
        $this->assertDatabaseHas('document_revisions', [
            'id' => $revision->id,
            'pricing_status' => 'full_price',
            'manual_missing_price_count' => 0,
            'cogm_import_original_name' => 'COGM Manual.xlsx',
        ]);
        $this->assertDatabaseHas('cogm_submissions', [
            'id' => $submission->id,
            'cogm_value' => 1674.73,
            'update_count' => 2,
        ]);
    }

    public function test_cancel_requires_reason_and_status_is_recorded(): void
    {
        $marketing=User::factory()->create(['name'=>'Marketing User','role'=>'marketing']);
        $submission=$this->submission();
        $this->actingAs($marketing)->from(route('marketing.cogm-inbox'))->post(route('marketing.cogm-status.update',$submission),['marketing_status'=>'cancel'])->assertSessionHasErrors('reason');
        $this->actingAs($marketing)->post(route('marketing.cogm-status.update',$submission),['marketing_status'=>'cancel','reason'=>'Customer menghentikan project.'])->assertRedirect();
        $this->assertDatabaseHas('cogm_submissions',['id'=>$submission->id,'marketing_status'=>'cancel','marketing_status_reason'=>'Customer menghentikan project.']);
        $this->assertDatabaseHas('cogm_submission_events',['cogm_submission_id'=>$submission->id,'event_type'=>'status']);
    }

    public function test_marketing_can_download_latest_costing_update_file(): void
    {
        Storage::fake('local');
        $marketing=User::factory()->create(['name'=>'Marketing User','role'=>'marketing']);
        $submission=$this->submission();
        Storage::put('workflow/costing-revisions/update.xlsx','excel-content');
        ProjectDocumentRevision::create(['document_project_id'=>$submission->revision->document_project_id,'document_revision_id'=>$submission->document_revision_id,'workflow_task_id'=>null,'revision_type'=>'price','original_name'=>'update-harga.xlsx','file_path'=>'workflow/costing-revisions/update.xlsx','uploaded_by'=>$marketing->id]);

        $this->actingAs($marketing)->get(route('marketing.cogm-update.download',$submission))->assertOk()->assertDownload('update-harga.xlsx');
    }

    public function test_die_go_status_marks_revision_as_a05_for_dashboard(): void
    {
        $marketing=User::factory()->create(['name'=>'Marketing User','role'=>'marketing']);
        $submission=$this->submission();
        $this->actingAs($marketing)->post(route('marketing.cogm-status.update',$submission),['marketing_status'=>'die_go'])->assertRedirect();
        $this->assertDatabaseHas('document_revisions',['id'=>$submission->document_revision_id,'a05'=>'ada','a04'=>'belum_ada']);
    }
}
