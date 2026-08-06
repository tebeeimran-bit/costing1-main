<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentRevision;
use App\Models\DocumentControlRegistration;
use App\Models\ProjectWorkflowTask;
use App\Models\Plant;
use App\Models\Pic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectA00WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_costing_inbox(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('costing.inbox'))
            ->assertOk()
            ->assertSee('Daftar Progress Costing')
            ->assertSee('Aktif')
            ->assertSee('History')
            ->assertSee('Tidak ada progress costing pada filter ini.');
    }

    public function test_document_control_registration_can_be_updated_from_embedded_modal(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $registration = DocumentControlRegistration::create([
            'registration_no' => 'A39',
            'registration_date' => '2026-07-31',
            'part_number' => 'W40296',
            'created_by' => $user->id,
            'row_order' => 1000,
        ]);

        $this->actingAs($user)->putJson(route('document-control.update', $registration), [
            'registration_no' => 'A39',
            'registration_date' => '2026-07-31',
            'customer' => 'Samsung',
            'project' => 'K6MA',
            'part_number' => 'W40296',
            'part_name' => 'WIRING HARNESS UPDATED',
            'pd_distribution' => '2026-07-31',
            'qa_distribution' => '2026-07-31',
            'pnp_qt_distribution' => '2026-07-31',
        ])->assertOk()->assertJson([
            'message' => 'Registrasi berhasil diperbarui.',
        ]);

        $this->assertDatabaseHas('document_control_registrations', [
            'id' => $registration->id,
            'part_name' => 'WIRING HARNESS UPDATED',
            'pd_distribution' => '2026-07-31 00:00:00',
        ]);
    }

    public function test_manual_drawing_registration_creates_project_without_a00_and_starts_breakdown(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Customer::create(['code' => 'SMS', 'name' => 'Samsung']);
        BusinessCategory::create(['code' => 'WH', 'name' => 'WIRING HARNESS']);

        $this->actingAs($user)->post(route('document-control.store'), [
            'create_manual_project' => 1, 'complete_distribution' => 1,
            'registration_no' => 'A-MANUAL-01', 'registration_date' => '2026-08-04',
            'drawing_type' => 'Drawing Assy', 'customer' => 'Samsung', 'project' => 'K8MA',
            'part_number' => 'W40999', 'part_name' => 'WIRING HARNESS', 'revision_number' => 'V0',
            'drawing_status' => 'New Drawing', 'business_category' => 'WIRING HARNESS',
            'pd_distribution' => '2026-08-04',
        ])->assertRedirect(route('document-control.inbox'));

        $this->assertDatabaseHas('document_projects', ['customer' => 'Samsung', 'model' => 'K8MA', 'part_number' => 'W40999']);
        $this->assertDatabaseHas('document_revisions', ['a00' => 'tidak ada']);
        $this->assertDatabaseHas('document_control_registrations', ['registration_no' => 'A-MANUAL-01', 'a00' => 'tidak ada']);
        $this->assertDatabaseHas('project_workflow_tasks', ['stage' => 'drawing', 'status' => 'completed']);
        $this->assertDatabaseHas('project_workflow_tasks', ['stage' => 'breakdown', 'status' => 'pending']);
        $this->actingAs($user)->get(route('project'))->assertOk()->assertSeeInOrder(['No. Assy', 'W40999']);

        $projectId = \App\Models\DocumentProject::where('part_number', 'W40999')->value('id');
        $this->actingAs($user)->delete(route('project.group.destroy'), [
            'project_ids' => [$projectId],
        ])->assertRedirect(route('project'));
        $this->assertDatabaseMissing('document_projects', ['id' => $projectId]);
    }

    public function test_admin_can_render_a00_create_form(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $defaultDate = date('Y-m-d');
        $this->actingAs($user)->get(route('control-project.a00.create'))
            ->assertOk()
            ->assertSee('General Information')
            ->assertSee('name="due_part_list" value="'.$defaultDate.'"', false)
            ->assertSee('name="due_umh" value="'.$defaultDate.'"', false);
    }

    public function test_publishing_a00_creates_project_and_v0_revision(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $category=BusinessCategory::create(['code'=>'WH','name'=>'Wiring Harness']);
        $customer=Customer::create(['code'=>'TDII','name'=>'PT. TOYO DENSO INDONESIA']);
        $plant=Plant::create(['code'=>'DEM','name'=>'Dharma Electrindo Mfg']);
        Pic::create(['name'=>'Engineer Test','type'=>'engineering']);
        Pic::create(['name'=>'Marketing Test','type'=>'marketing']);

        $response=$this->actingAs($user)->post(route('control-project.a00.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'plant_id'=>$plant->id,'period'=>'2026-08','pic_engineering'=>'Engineer Test','pic_marketing'=>'Marketing Test',
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
        $this->assertDatabaseHas('document_revisions',['plant_id'=>$plant->id,'period'=>'2026-08','pic_engineering'=>'Engineer Test','pic_marketing'=>'Marketing Test']);
        $this->assertDatabaseHas('project_workflow_tasks',['stage'=>'drawing','assigned_role'=>'document_control','status'=>'pending']);

        $a00=\App\Models\ProjectA00Form::firstOrFail();
        $this->actingAs($user)->put(route('control-project.a00.update-operational',$a00),[
            'plant_id'=>$plant->id,'period'=>'2026-09',
            'pic_engineering'=>'Engineer Updated','pic_marketing'=>'Marketing Updated',
        ])->assertRedirect(route('control-project.a00.index'));
        $this->assertDatabaseHas('document_revisions',[
            'plant_id'=>$plant->id,'period'=>'2026-09',
            'pic_engineering'=>'Engineer Updated','pic_marketing'=>'Marketing Updated',
        ]);
        $a00Item=$a00->items()->firstOrFail();
        $this->actingAs($user)->put(route('control-project.a00.update',$a00),[
            'document_number'=>'0100/MKT-PROJECT/A00/VII/2026','document_date'=>'2026-08-04','revision'=>'01',
            'from_department'=>'MKT','to_department'=>'TEAM PROJECT','business_category_id'=>$category->id,
            'customer_id'=>$customer->id,'plant_id'=>$plant->id,'period'=>'2026-09',
            'pic_engineering'=>'Engineer Updated','pic_marketing'=>'Marketing Updated','issue_location'=>'Cikarang',
            'items'=>[['id'=>$a00Item->id,'model'=>'K4MA-EDIT','assy_name'=>'CORD ASSY EDIT','assy_number'=>'W40294-EDIT',
                'quantity'=>900000,'quantity_uom'=>'Pcs','quantity_basis'=>'per Year','product_life_years'=>3,'spot_order'=>0]],
        ])->assertRedirect(route('control-project.a00.index'));
        $this->assertDatabaseHas('project_a00_forms',['id'=>$a00->id,'revision'=>'01','model'=>'K4MA-EDIT','assy_number'=>'W40294-EDIT']);
        $this->assertDatabaseHas('document_projects',['model'=>'K4MA-EDIT','part_number'=>'W40294-EDIT','part_name'=>'CORD ASSY EDIT']);
        $revisionForForm=DocumentRevision::firstOrFail();
        $this->actingAs($user)->get(route('form',['tracking_revision_id'=>$revisionForForm->id]))
            ->assertOk()->assertSee('value="2026-09" selected',false);

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
        $this->actingAs($user)->get(route('document-control.inbox'))
            ->assertOk()
            ->assertSee('Lengkapi Distribusi')
            ->assertSee('Drawing Assy develop')
            ->assertSee($customer->name)
            ->assertSee($category->name);

        $this->actingAs($user)->post(route('document-control.tasks.complete',$task))
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('project_workflow_tasks',['stage'=>'breakdown']);

        $this->actingAs($user)->post(route('document-control.store'),[
            'workflow_task_id'=>$task->id,'complete_distribution'=>1,
            'registration_no'=>'A38-UPDATED','registration_date'=>'2026-08-03',
            'customer'=>$customer->name,'project'=>'K4MA','part_number'=>'W40294',
            'part_name'=>'CORD ASSY','drawing_status'=>'New Drawing','pd_distribution'=>'2026-08-04',
        ])->assertRedirect(route('document-control.inbox'));
        $this->actingAs($user)->post(route('document-control.tasks.complete',$task))->assertRedirect(route('document-control.inbox'));

        $this->assertDatabaseHas('project_workflow_tasks',[
            'id'=>$task->id,'stage'=>'drawing','status'=>'completed','completed_by_id'=>$user->id,
        ]);
        $this->assertDatabaseHas('project_workflow_tasks',[
            'stage'=>'breakdown','assigned_role'=>'admin_costing','status'=>'pending',
        ]);
        $this->assertSame(1,ProjectWorkflowTask::where('stage','breakdown')->count());

        Storage::fake('local');
        $breakdownTask=ProjectWorkflowTask::where('stage','breakdown')->firstOrFail();
        $this->assertSame(['QA','PNP & QT','PPE/PME'], $breakdownTask->metadata['pending_distributions']);
        $this->actingAs($user)->get(route('breakdown.inbox'))->assertOk()->assertSee('A38-UPDATED');
        $this->actingAs($user)->post(route('breakdown.tasks.complete',$breakdownTask), [
            'partlist_file'=>UploadedFile::fake()->createWithContent('partlist.pdf', '%PDF-1.4 partlist'),
        ])->assertRedirect(route('breakdown.inbox'));
        $this->assertDatabaseHas('project_workflow_tasks',[
            'id'=>$breakdownTask->id,'status'=>'in_progress','completed_by_id'=>null,
        ]);
        $this->assertDatabaseHas('project_workflow_tasks',[
            'stage'=>'costing','assigned_role'=>'admin_costing','status'=>'pending',
        ]);
        $this->actingAs($user)->get(route('breakdown.inbox'))
            ->assertOk()->assertSee('Menunggu UMH')->assertSee('partlist.pdf');
        $this->actingAs($user)->get(route('project'))
            ->assertOk()->assertSee('Menunggu UMH')->assertSee('Siap dimulai');
        $this->actingAs($user)->post(route('breakdown.tasks.start-costing',$breakdownTask))
            ->assertRedirect(route('form',['tracking_revision_id'=>$task->document_revision_id]));
        $this->assertDatabaseHas('project_workflow_tasks',[
            'stage'=>'costing','assigned_role'=>'admin_costing','status'=>'in_progress',
        ]);

        $this->actingAs($user)->post(route('breakdown.tasks.complete',$breakdownTask), [
            'umh_file'=>UploadedFile::fake()->createWithContent('umh.pdf', '%PDF-1.4 umh'),
        ])->assertRedirect(route('breakdown.inbox'));
        $this->assertDatabaseHas('project_workflow_tasks',[
            'id'=>$breakdownTask->id,'status'=>'completed','completed_by_id'=>$user->id,
        ]);
        $this->assertDatabaseHas('project_workflow_tasks',[
            'stage'=>'costing','assigned_role'=>'admin_costing','status'=>'in_progress',
        ]);
        $revision=$task->revision()->firstOrFail();
        $this->assertSame('partlist.pdf',$revision->partlist_original_name);
        $this->assertSame('umh.pdf',$revision->umh_original_name);
        Storage::disk('local')->assertExists($revision->partlist_file_path);
        Storage::disk('local')->assertExists($revision->umh_file_path);
    }
}
