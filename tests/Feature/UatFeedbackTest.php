<?php

namespace Tests\Feature;

use App\Models\UatFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_report_a_problem(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->post(route('uat-feedback.store'), [
            'category' => 'usability',
            'severity' => 'high',
            'title' => 'Tombol sulit ditemukan',
            'description' => 'Tombol aksi tidak terlihat pada layar kecil.',
            'page_url' => 'http://localhost/project',
            'route_name' => 'project',
            'browser' => 'Test Browser',
        ])->assertRedirect();

        $this->assertDatabaseHas('uat_feedback', [
            'user_id' => $user->id,
            'category' => 'usability',
            'severity' => 'high',
            'status' => 'open',
        ]);
        $this->assertSame('http://localhost/project', UatFeedback::latest()->value('page_url'));
    }

    public function test_external_context_url_is_discarded_and_validation_uses_uat_error_bag(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->post(route('uat-feedback.store'), [
            'category' => 'bug', 'severity' => 'medium', 'title' => 'External URL',
            'description' => 'Testing context sanitization.', 'page_url' => 'javascript:alert(1)',
        ])->assertRedirect();

        $this->assertNull(UatFeedback::latest()->value('page_url'));

        $this->actingAs($user)->post(route('uat-feedback.store'), [
            'category' => 'bug', 'severity' => 'medium', 'description' => 'Judul kosong.',
        ])->assertSessionHasErrorsIn('uatFeedback', ['title']);
    }

    public function test_only_admin_can_review_and_resolve_feedback(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = UatFeedback::create([
            'user_id' => $viewer->id,
            'category' => 'bug',
            'severity' => 'critical',
            'title' => 'Gagal menyimpan',
            'description' => 'Form tidak dapat disimpan.',
            'status' => 'open',
        ]);

        $this->actingAs($viewer)->get(route('uat-feedback.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('uat-feedback.index'))
            ->assertOk()
            ->assertSee('Gagal menyimpan');

        $this->actingAs($admin)->patch(route('uat-feedback.update', $feedback), [
            'status' => 'resolved',
            'resolution_notes' => 'Validasi form sudah diperbaiki.',
        ])->assertRedirect();

        $this->assertDatabaseHas('uat_feedback', [
            'id' => $feedback->id,
            'status' => 'resolved',
            'resolved_by_id' => $admin->id,
        ]);
    }
}
