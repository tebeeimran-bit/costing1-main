<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentRevision;
use App\Models\DocumentControlRegistration;
use App\Models\ProjectWorkflowTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectA00WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_a00_create_form(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $this->actingAs($user)->get(route('control-project.a00.create'))->assertOk()->assertSee('General Information');
    }

    public function test_publishing_a00_creates_project_and_v0_revision(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $category=BusinessCategory::create(['code'=>'WH','name'=>'Wiring Harness']);
        $customer=Customer::create(['code'=>'TDII','name'=>'PT. TOYO DENSO INDONESIA']);

        $response=$this->actingAs($user)->post(route('control-project.a00.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'items'=>[['model'=>'K4MA','assy_name'=>'CORD ASSY','assy_number'=>'W40294','quantity'=>810000,'quantity_uom'=>'Pcs','quantity_basis'=>'per Year']],
            'document_number'=>'0100/MKT-PROJECT/A00/VII/2026','document_date'=>'2026-07-29','revision'=>'00',
            'from_department'=>'MKT','to_department'=>'TEAM PROJECT','quantity_uom'=>'Pcs',
            'quantity_basis'=>'per Year','issue_location'=>'Cikarang',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_a00_forms',['model'=>'K4MA','assy_number'=>'W40294','status'=>'issued']);
        $this->assertDatabaseHas('document_projects',['customer'=>$customer->name,'model'=>'K4MA','part_number'=>'W40294']);
        $this->assertDatabaseHas('project_a00_items',['model'=>'K4MA','assy_number'=>'W40294','quantity'=>810000]);
        $this->assertDatabaseHas('document_revisions',['version_number'=>1,'status'=>DocumentRevision::STATUS_A00_ISSUED]);
        $this->assertDatabaseHas('project_workflow_tasks',['stage'=>'drawing','assigned_role'=>'document_control','status'=>'pending']);

        $task=ProjectWorkflowTask::where('stage','drawing')->firstOrFail();
        foreach(['A38','A38-UPDATED'] as $registrationNumber){
            $this->actingAs($user)->post(route('document-control.store'),[
                'workflow_task_id'=>$task->id,'registration_no'=>$registrationNumber,
                'registration_date'=>'2026-08-03','customer'=>$customer->name,'project'=>'K4MA',
                'part_number'=>'W40294','part_name'=>'CORD ASSY','drawing_status'=>'New Drawing',
            ])->assertRedirect();
        }
        $this->assertSame(1,DocumentControlRegistration::where('workflow_task_id',$task->id)->count());
        $this->assertDatabaseHas('document_control_registrations',['workflow_task_id'=>$task->id,'registration_no'=>'A38-UPDATED']);
    }
}
