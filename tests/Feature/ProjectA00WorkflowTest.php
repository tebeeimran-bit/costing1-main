<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\CostingGroup;
use App\Models\CostingData;
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
        $this->actingAs($user)->get(route('control-project.a00.index'))
            ->assertOk()->assertSee('W40999')->assertSee('+ Registrasi Drawing')->assertSee('Menunggu A00');
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

    public function test_manual_breakdown_creates_project_and_active_breakdown_task(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $customer=Customer::create(['code'=>'MAN-BD','name'=>'Manual Breakdown Customer']);
        $category=BusinessCategory::create(['code'=>'BD','name'=>'Breakdown Category']);

        $this->actingAs($user)->get(route('breakdown.inbox'))
            ->assertOk()->assertSee('+ Breakdown')->assertSee('Tambah Breakdown Manual');
        $this->actingAs($user)->post(route('breakdown.manual.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'model'=>'KBD1','assy_name'=>'MANUAL ASSY','assy_number'=>'BD-001',
            'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Manual','pic_marketing'=>'Marketing Manual',
            'notes'=>'Dibuat dari Inbox Breakdown',
        ])->assertRedirect(route('breakdown.inbox'))->assertSessionHas('success');

        $this->assertDatabaseHas('document_projects',['customer'=>$customer->name,'model'=>'KBD1','part_number'=>'BD-001']);
        $this->assertDatabaseHas('document_revisions',['a00'=>'tidak ada','pic_engineering'=>'Engineer Manual','pic_marketing'=>'Marketing Manual']);
        $this->assertDatabaseMissing('project_workflow_tasks',['stage'=>'drawing']);
        $this->assertDatabaseHas('project_workflow_tasks',['stage'=>'breakdown','status'=>'pending']);
        $this->actingAs($user)->get(route('project'))->assertOk()->assertSee('BD-001')->assertSee('Sedang Breakdown');
    }

    public function test_manual_breakdown_project_is_listed_and_can_receive_a00_without_duplication(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $customer=Customer::create(['code'=>'WAIT-A00','name'=>'Customer Waiting A00']);
        $category=BusinessCategory::create(['code'=>'WA','name'=>'Waiting A00 Category']);
        $plant=Plant::create(['code'=>'DEM','name'=>'Dharma Electrindo Mfg']);

        $this->actingAs($user)->post(route('breakdown.manual.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'model'=>'WAIT1','assy_name'=>'WAITING ASSY','assy_number'=>'WA-001',
            'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Waiting','pic_marketing'=>'Marketing Waiting',
        ])->assertRedirect(route('breakdown.inbox'));

        $project=\App\Models\DocumentProject::where('part_number','WA-001')->firstOrFail();
        $this->actingAs($user)->get(route('control-project.a00.index'))
            ->assertOk()->assertSee('Project Menunggu Pembuatan A00')->assertSee('WA-001')->assertSee('+ Breakdown');
        $this->actingAs($user)->get(route('control-project.a00.create',['project_id'=>$project->id]))
            ->assertOk()->assertSee('WAIT1')->assertSee('WA-001')->assertSee('Membuat A00 untuk project yang sudah terdaftar');

        $this->actingAs($user)->post(route('control-project.a00.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'plant_id'=>$plant->id,'period'=>'2026-08','pic_engineering'=>'Engineer Waiting','pic_marketing'=>'Marketing Waiting',
            'items'=>[['document_project_id'=>$project->id,'model'=>'WAIT1','assy_name'=>'WAITING ASSY','assy_number'=>'WA-001','quantity'=>1000,'quantity_uom'=>'Pcs','quantity_basis'=>'per Year']],
            'document_number'=>'WAIT/MKT-PROJECT/A00/VIII/2026','document_date'=>'2026-08-07','revision'=>'00',
            'from_department'=>'MKT','to_department'=>'TEAM PROJECT','issue_location'=>'Cikarang',
        ])->assertRedirect();

        $this->assertDatabaseCount('document_projects',1);
        $this->assertDatabaseHas('project_a00_items',['document_project_id'=>$project->id,'assy_number'=>'WA-001']);
        $this->assertDatabaseHas('document_revisions',['document_project_id'=>$project->id,'a00'=>'ada']);
        $this->assertDatabaseHas('project_workflow_tasks',['document_project_id'=>$project->id,'stage'=>'breakdown']);
    }

    public function test_active_business_category_filters_control_project_and_breakdown_inbox(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $wh=BusinessCategory::create(['code'=>'WH','name'=>'WIRING HARNESS']);
        $aep=BusinessCategory::create(['code'=>'AEP','name'=>'AUTOMOTIVE ELECTRONIC PART']);
        $customer=Customer::create(['code'=>'FILTER','name'=>'Filter Customer']);

        foreach ([[$wh,'WH-MODEL','WH-ASSY'],[$aep,'AEP-MODEL','AEP-ASSY']] as [$category,$model,$assy]) {
            $this->actingAs($user)->post(route('breakdown.manual.store'),[
                'business_category_id'=>$category->id,'customer_id'=>$customer->id,
                'model'=>$model,'assy_name'=>$category->name,'assy_number'=>$assy,
                'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Filter',
            ])->assertRedirect(route('breakdown.inbox'));
        }

        $session=[\App\Support\BusinessCategoryContext::SESSION_KEY=>$wh->id];
        $this->actingAs($user)->withSession($session)->get(route('control-project.a00.index'))
            ->assertOk()->assertSee('WH-ASSY')->assertDontSee('AEP-ASSY');
        $this->actingAs($user)->withSession($session)->get(route('breakdown.inbox'))
            ->assertOk()->assertSee('WH-ASSY')->assertDontSee('AEP-ASSY');

        $this->actingAs($user)->from(route('control-project.a00.index'))->post(route('business-category-context.update'),[
            'business_category_id'=>$aep->id,
        ])->assertRedirect(route('control-project.a00.index'))->assertSessionHas(\App\Support\BusinessCategoryContext::SESSION_KEY,$aep->id);
    }

    public function test_manual_breakdown_project_can_receive_drawing_registration_without_duplication(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $customer=Customer::create(['code'=>'DRAW-BD','name'=>'Drawing Breakdown Customer']);
        $category=BusinessCategory::create(['code'=>'DBD','name'=>'Drawing Breakdown Category']);

        $this->actingAs($user)->post(route('breakdown.manual.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'model'=>'DBD1','assy_name'=>'DRAWING ASSY','assy_number'=>'DBD-001',
            'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Drawing',
        ])->assertRedirect(route('breakdown.inbox'));

        $project=\App\Models\DocumentProject::where('part_number','DBD-001')->firstOrFail();
        $revision=$project->revisions()->firstOrFail();
        $this->actingAs($user)->get(route('document-control.inbox'))
            ->assertOk()->assertSee('Project Breakdown Menunggu Registrasi Drawing')
            ->assertSee('DBD-001')->assertSee('Buat Registrasi Drawing');

        $this->actingAs($user)->post(route('document-control.store'),[
            'manual_project_id'=>$project->id,'complete_distribution'=>1,
            'registration_no'=>'REG-DBD-001','registration_date'=>'2026-08-07',
            'drawing_type'=>'Drawing Assy','customer'=>$project->customer,'project'=>$project->model,
            'part_number'=>$project->part_number,'part_name'=>$project->part_name,
            'revision_number'=>'V0','drawing_status'=>'New Drawing','business_category'=>$category->name,
            'a00'=>'tidak ada','pd_distribution'=>'2026-08-07',
        ])->assertRedirect(route('document-control.inbox'));

        $this->assertDatabaseCount('document_projects',1);
        $this->assertDatabaseCount('document_revisions',1);
        $this->assertDatabaseHas('document_control_registrations',[
            'document_project_id'=>$project->id,'document_revision_id'=>$revision->id,'registration_no'=>'REG-DBD-001',
        ]);
        $this->assertSame(1,ProjectWorkflowTask::where('document_revision_id',$revision->id)->where('stage','breakdown')->count());
        $this->assertDatabaseHas('project_workflow_tasks',[
            'document_revision_id'=>$revision->id,'stage'=>'drawing','status'=>'completed',
        ]);
    }

    public function test_breakdown_project_can_upload_partlist_revision_excel(): void
    {
        Storage::fake('local');
        $user=User::factory()->create(['role'=>'admin']);
        $customer=Customer::create(['code'=>'REV-DOC','name'=>'Revision Customer']);
        $category=BusinessCategory::create(['code'=>'REV','name'=>'Revision Category']);
        $this->actingAs($user)->post(route('breakdown.manual.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'model'=>'REV1','assy_name'=>'REVISION ASSY','assy_number'=>'REV-001',
            'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Revision',
        ])->assertRedirect(route('breakdown.inbox'));

        $task=ProjectWorkflowTask::where('stage','breakdown')->firstOrFail();
        $file=UploadedFile::fake()->create('partlist-revisi-01.xlsx',120,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($user)->post(route('breakdown.tasks.revision',$task),[
            'revision_type'=>'partlist','revision_file'=>$file,
            'description'=>'Perbaikan quantity material.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('project_document_revisions',[
            'document_project_id'=>$task->document_project_id,'document_revision_id'=>$task->document_revision_id,
            'revision_type'=>'partlist','original_name'=>'partlist-revisi-01.xlsx',
            'description'=>'Perbaikan quantity material.',
        ]);
        $task->revision->refresh();
        $this->assertSame('partlist-revisi-01.xlsx',$task->revision->partlist_original_name);
        Storage::disk('local')->assertExists($task->revision->partlist_file_path);
        $this->assertDatabaseCount('document_revisions',1);
    }

    public function test_saving_breakdown_automatically_sends_project_to_costing_inbox(): void
    {
        Storage::fake('local');
        $user=User::factory()->create(['role'=>'admin']);
        $customer=Customer::create(['code'=>'AUTO-COST','name'=>'Automatic Costing Customer']);
        $category=BusinessCategory::create(['code'=>'AC','name'=>'Automatic Costing Category']);
        $this->actingAs($user)->post(route('breakdown.manual.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,
            'model'=>'AC01','assy_name'=>'AUTOMATIC COSTING ASSY','assy_number'=>'AC-001',
            'received_date'=>'2026-08-07','pic_engineering'=>'Engineer Costing',
        ])->assertRedirect(route('breakdown.inbox'));

        $task=ProjectWorkflowTask::where('stage','breakdown')->firstOrFail();
        $this->actingAs($user)->post(route('breakdown.tasks.complete',$task),[
            'partlist_file'=>UploadedFile::fake()->create('partlist.xlsx',100,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'umh_file'=>UploadedFile::fake()->create('umh.xlsx',100,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])->assertRedirect(route('breakdown.inbox'))->assertSessionHas('success');

        $this->assertDatabaseHas('project_workflow_tasks',[
            'document_revision_id'=>$task->document_revision_id,
            'stage'=>ProjectWorkflowTask::STAGE_COSTING,
            'status'=>ProjectWorkflowTask::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('costing_data',['tracking_revision_id'=>$task->document_revision_id]);
        $this->actingAs($user)->get(route('costing.inbox'))
            ->assertOk()->assertSee('AC-001')->assertSee('Form Costing');
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
        $this->assertDatabaseHas('costing_groups',['mode'=>'normal','status'=>'draft','pic_engineering'=>'Engineer Test','pic_marketing'=>'Marketing Test']);
        $this->assertDatabaseHas('costing_group_items',['sequence'=>1,'status'=>'pending','quantity'=>810000]);

        $a00=\App\Models\ProjectA00Form::firstOrFail();
        $this->actingAs($user)->get(route('control-project.a00.show', $a00))
            ->assertOk()
            ->assertSee('Download PDF')
            ->assertDontSee('Buat Snapshot Draft');
        $pdfResponse=$this->actingAs($user)->get(route('control-project.a00.pdf',$a00));
        $pdfResponse->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF-', (string)$pdfResponse->getContent());
        $this->assertStringContainsString('attachment;',strtolower((string)$pdfResponse->headers->get('content-disposition')));
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

    public function test_multi_item_a00_creates_bulky_group_and_incomplete_draft_snapshot(): void
    {
        $user=User::factory()->create(['role'=>'admin']);
        $category=BusinessCategory::create(['code'=>'WHB','name'=>'Wiring Harness Bulky']);
        $customer=Customer::create(['code'=>'BULK','name'=>'Bulk Customer']);
        $plant=Plant::create(['code'=>'BULK','name'=>'Bulk Plant']);

        $this->actingAs($user)->post(route('control-project.a00.store'),[
            'business_category_id'=>$category->id,'customer_id'=>$customer->id,'plant_id'=>$plant->id,
            'period'=>'2026-08','pic_engineering'=>'Engineer Bulky','pic_marketing'=>'Marketing Bulky',
            'items'=>[
                ['model'=>'M1','assy_name'=>'ASSY 1','assy_number'=>'B001','quantity'=>10,'quantity_uom'=>'Pcs','quantity_basis'=>'per Year'],
                ['model'=>'M2','assy_name'=>'ASSY 2','assy_number'=>'B002','quantity'=>null,'quantity_uom'=>'Pcs','quantity_basis'=>'per Year'],
            ],
            'document_number'=>'BULKY/A00/001','document_date'=>'2026-08-07','revision'=>'00',
            'from_department'=>'MKT','to_department'=>'TEAM PROJECT','issue_location'=>'Cikarang',
        ])->assertRedirect();

        $group=CostingGroup::with('items')->firstOrFail();
        $this->assertSame(CostingGroup::MODE_BULKY,$group->mode);
        $this->assertCount(2,$group->items);
        $this->actingAs($user)->get(route('document-control.inbox'))
            ->assertOk()->assertSee('Lihat Item')->assertSee('Menunggu Distribusi')->assertSee('2 item');
        $pdfA00=\App\Models\ProjectA00Form::with('items')->findOrFail($group->project_a00_form_id);
        $a00PdfHtml=view('control-project.a00.pdf',['a00'=>$pdfA00,'logoData'=>null])->render();
        $this->assertStringContainsString('Terlampir',$a00PdfHtml);
        $this->assertStringContainsString('MASSPRO',$a00PdfHtml);
        $this->assertStringContainsString('B001',$a00PdfHtml);
        $this->assertStringContainsString('B002',$a00PdfHtml);
        $bulkyPdf=$this->actingAs($user)->get(route('control-project.a00.pdf',$pdfA00));
        $bulkyPdf->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF-',(string)$bulkyPdf->getContent());
        $version=app(\App\Services\Costing\BulkyCogmSnapshotService::class)->create($group,'draft',$user->id);
        $this->assertTrue($version->has_incomplete_price);
        $this->assertTrue($version->has_incomplete_quantity);
        $this->assertCount(2,$version->items);
        $this->assertDatabaseHas('costing_group_events',['costing_group_id'=>$group->id,'event_type'=>'draft_generated']);
        $recipient=User::factory()->create(['name'=>'Marketing Item Bulky','role'=>'marketing']);
        $item=$group->items->first();
        $this->actingAs($user)->patch(route('control-project.costing-group-items.pics',$item),[
            'pic_engineering'=>null,'pic_marketing'=>'Marketing Item Bulky',
        ])->assertRedirect();
        $this->assertSame('Marketing Item Bulky',$item->fresh()->effectivePicMarketing());
        $this->assertDatabaseHas('notifications',['notifiable_id'=>$recipient->id,'notifiable_type'=>User::class]);
        $notification=$recipient->unreadNotifications()->firstOrFail();
        $this->actingAs($recipient)->post(route('notifications.open',$notification))->assertRedirect(route('project',absolute:false));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($user)->post(route('control-project.costing-groups.items.add',$group),[
            'model'=>'M3','assy_name'=>'ASSY 3','assy_number'=>'B003','quantity'=>30,
            'quantity_uom'=>'Pcs','quantity_basis'=>'per Year','pic_marketing'=>'Marketing Item Bulky',
            'reason'=>'Tambahan kebutuhan customer',
        ])->assertRedirect();
        $added=$group->items()->whereHas('a00Item',fn($query)=>$query->where('assy_number','B003'))->firstOrFail();
        $this->assertDatabaseHas('project_workflow_tasks',['document_revision_id'=>$added->active_document_revision_id,'stage'=>'drawing','status'=>'pending']);
        $this->assertDatabaseHas('costing_group_events',['costing_group_item_id'=>$added->id,'event_type'=>'item_added']);
        $this->actingAs($user)->delete(route('control-project.costing-group-items.remove',$added),['reason'=>'Dibatalkan customer'])->assertRedirect();
        $this->assertNotNull($added->fresh()->removed_at);
        $this->assertDatabaseHas('costing_group_events',['costing_group_item_id'=>$added->id,'event_type'=>'item_removed','reason'=>'Dibatalkan customer']);
        foreach($group->fresh()->activeItems()->with(['project','revision','a00Item'])->get() as $activeItem){
            if($activeItem->quantity===null) $activeItem->a00Item->update(['quantity'=>20]);
            $costing=CostingData::create(['product_id'=>$activeItem->project->product_id,'customer_id'=>$customer->id,'tracking_revision_id'=>$activeItem->revision->id,'period'=>'2026-08']);
            $activeItem->revision->update(['status'=>DocumentRevision::STATUS_APPROVED_BY_COORDINATOR]);
        }
        app(\App\Services\Costing\CostingGroupService::class)->syncFromA00($group->a00Form,$user->id);
        Storage::fake('local');
        $this->actingAs($user)->post(route('control-project.costing-groups.final-file',$group),[
            'final_file'=>UploadedFile::fake()->create('Bulky-Final.xlsx',20,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'change_summary'=>'Final awal bulky',
        ])->assertRedirect()->assertSessionHas('success');
        $superseded=$group->versions()->where('type','final')->latest('version_number')->firstOrFail();
        $this->actingAs($user)->post(route('control-project.costing-groups.final-file',$group),[
            'final_file'=>UploadedFile::fake()->create('Bulky-Final-Updated.xlsx',20,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'change_summary'=>'Final pengganti',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('superseded',$superseded->fresh()->status);
        $this->actingAs($user)->post(route('control-project.costing-groups.submit-final',$group))
            ->assertRedirect()->assertSessionHas('success');
        $finalVersion=$group->versions()->where('type','final')->where('status','submitted')->firstOrFail();
        $this->assertSame('submitted',$finalVersion->status);
        $this->assertSame($finalVersion->id,$group->fresh()->last_submitted_version_id);
        $this->actingAs($user)->post(route('control-project.costing-groups.submit-final',$group))->assertStatus(422);
        $this->actingAs($user)->get(route('marketing.cogm-inbox'))->assertOk()->assertSee('Bulky COGM per A00')->assertSee('BULKY/A00/001');
        $this->actingAs($user)->get(route('marketing.cogm-inbox',['search'=>'BULKY/A00/001']))
            ->assertOk()->assertSee('BULKY/A00/001')->assertSee('Reset');
        $coordinator=User::factory()->create(['name'=>'Coordinator Bulky','role'=>'coordinator_costing']);
        $this->actingAs($coordinator)->get(route('costing-groups.workspace',$group))->assertOk()->assertSee('Approve Group');
        $this->actingAs($coordinator)->get(route('control-project.a00.show',$group->a00Form))->assertForbidden();
        $this->actingAs($recipient)->get(route('marketing.bulky-cogm.download',$finalVersion))->assertOk();
        $otherMarketing=User::factory()->create(['name'=>'Marketing Lain','role'=>'marketing']);
        $this->actingAs($otherMarketing)->get(route('marketing.bulky-cogm.download',$finalVersion))->assertForbidden();
        Storage::put($finalVersion->file_path,'tampered');
        $this->actingAs($recipient)->get(route('marketing.bulky-cogm.download',$finalVersion))->assertStatus(409);
        $this->actingAs($user)->get(route('costing-groups.workspace',$group))
            ->assertOk()->assertSee('Bulky COGM')->assertSee('Marketing Item Bulky')->assertSee('Riwayat:')
            ->assertSee('Terlampir')->assertSee('MASSPRO')->assertSee('Lampiran Item');
        $a00=$group->a00Form;
        $projectIds=$a00->items()->pluck('document_project_id')->all();
        $this->actingAs($user)->delete(route('control-project.a00.destroy',$a00))
            ->assertRedirect(route('control-project.a00.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('project_a00_forms',['id'=>$a00->id]);
        foreach($projectIds as $projectId) $this->assertDatabaseMissing('document_projects',['id'=>$projectId]);
    }
}
