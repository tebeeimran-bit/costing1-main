<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\ProjectManualTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTaskPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_personal_task_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('my-tasks'))
            ->assertOk()
            ->assertSee('Yang perlu Anda kerjakan sekarang')
            ->assertSee('Tidak ada task pada kategori ini');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('my-tasks'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_manual_task_attached_to_project(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $project = DocumentProject::create([
            'customer' => 'Customer A', 'model' => 'Model A', 'part_number' => 'PN-001',
            'part_name' => 'Harness A', 'project_key' => hash('sha256', 'project-a'),
        ]);

        $this->actingAs($user)->post(route('my-tasks.store'), [
            'document_project_id' => $project->id,
            'title' => 'Konfirmasi harga material',
            'category' => 'pricing',
            'priority' => 'high',
            'due_at' => '2026-08-01',
        ])->assertRedirect(route('my-tasks'));

        $this->assertDatabaseHas('project_manual_tasks', [
            'document_project_id' => $project->id,
            'assignee_id' => $user->id,
            'title' => 'Konfirmasi harga material',
        ]);

        $this->actingAs($user)->get(route('my-tasks'))
            ->assertOk()->assertSee('PN-001')->assertSee('Konfirmasi harga material');
    }

    public function test_manual_task_requires_a_project_and_can_be_completed(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post(route('my-tasks.store'), [
            'title' => 'Task tanpa project', 'category' => 'general', 'priority' => 'normal',
        ])->assertSessionHasErrors('document_project_id');

        $project = DocumentProject::create([
            'customer' => 'Customer B', 'model' => 'Model B', 'part_number' => 'PN-002',
            'part_name' => 'Harness B', 'project_key' => hash('sha256', 'project-b'),
        ]);
        $task = ProjectManualTask::create([
            'document_project_id' => $project->id, 'assignee_id' => $user->id,
            'created_by_id' => $user->id, 'title' => 'Task selesai',
        ]);

        $this->actingAs($user)->patch(route('my-tasks.update', $task), [
            'progress' => 50, 'status' => 'completed',
        ])->assertRedirect();

        $this->assertDatabaseHas('project_manual_tasks', [
            'id' => $task->id, 'status' => 'completed', 'progress' => 100,
        ]);
    }
}
