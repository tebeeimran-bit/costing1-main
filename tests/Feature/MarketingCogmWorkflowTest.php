<?php

namespace Tests\Feature;

use App\Models\CogmSubmission;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\ProjectDocumentRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
