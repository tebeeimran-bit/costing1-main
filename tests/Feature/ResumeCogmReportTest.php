<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeCogmReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_resume_cogm_report(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/resume-cogm');

        $response
            ->assertOk()
            ->assertSee('Tren COGM per Periode')
            ->assertSee('Ringkasan per Customer')
            ->assertSee('Detail COGM per Project');
    }
}
