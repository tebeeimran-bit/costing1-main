<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\ProjectManualTask;
use App\Models\User;
use App\Services\Notification\ProjectNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_dismiss_and_configure_notifications(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $revision = $this->revision($user);
        $service = app(ProjectNotificationService::class);
        $key = $service->forUser($user)->first()['key'];

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()->assertSee('Notification Center')->assertSee('Dokumen project belum ada');

        $this->actingAs($user)->patchJson(route('notifications.read'), ['key' => $key])->assertOk();
        $this->assertTrue((bool) $service->forUser($user)->firstWhere('key', $key)['is_read']);

        $this->actingAs($user)->post(route('notifications.dismiss'), ['key' => $key])->assertRedirect();
        $this->assertNull($service->forUser($user)->firstWhere('key', $key));

        $this->actingAs($user)->put(route('notifications.preferences'), ['enabled_types' => ['mention']])->assertRedirect();
        $this->assertCount(0, $service->forUser($user));
    }

    public function test_manual_task_deadline_notifies_assignee_and_escalates_to_creator(): void
    {
        $creator = User::factory()->create(['role' => 'admin_costing']);
        $assignee = User::factory()->create(['role' => 'editor']);
        $project = DocumentProject::create(['customer'=>'Customer','model'=>'Model','part_number'=>'TASK-01','part_name'=>'Task Project','project_key'=>hash('sha256',uniqid('',true))]);
        ProjectManualTask::create(['document_project_id'=>$project->id,'assignee_id'=>$assignee->id,'created_by_id'=>$creator->id,'title'=>'Review quotation','due_at'=>today()->subDays(2)]);
        $service = app(ProjectNotificationService::class);

        $this->assertTrue($service->forUser($assignee)->contains(fn($item)=>$item['type']==='task' && str_contains($item['line'],'Review quotation')));
        $this->assertTrue($service->forUser($creator)->contains(fn($item)=>$item['type']==='escalation' && str_contains($item['line'],'Review quotation')));
    }

    private function revision(User $user): DocumentRevision
    {
        $project = DocumentProject::create(['customer'=>'Customer','model'=>'Model','part_number'=>'NOTIF-01','part_name'=>'Notification Part','project_key'=>hash('sha256',uniqid('',true))]);
        $this->actingAs($user);
        return DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>'2026-07-16','pic_engineering'=>'PIC','partlist_original_name'=>'p.xlsx','partlist_file_path'=>'p.xlsx','umh_original_name'=>'u.xlsx','umh_file_path'=>'u.xlsx','status'=>DocumentRevision::STATUS_PENDING_FORM_INPUT]);
    }
}
