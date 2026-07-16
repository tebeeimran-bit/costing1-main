<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductivityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_finds_project_by_part_number(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        DocumentProject::create([
            'customer' => 'Toyota Astra Motor',
            'model' => 'D37D',
            'part_number' => 'WH-12345',
            'part_name' => 'Harness Main Body',
            'project_key' => hash('sha256', 'global-search-test'),
        ]);

        $this->actingAs($user)->getJson(route('global-search', ['q' => 'WH-123']))
            ->assertOk()
            ->assertJsonPath('results.0.type', 'Project')
            ->assertJsonPath('results.0.title', 'WH-12345 — Harness Main Body');
    }

    public function test_global_search_requires_authentication_and_valid_query(): void
    {
        $this->get(route('global-search', ['q' => 'project']))->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->getJson(route('global-search', ['q' => 'x']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($user)->getJson(route('global-search', ['q' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_help_center_renders_productivity_guidance(): void
    {
        $user = User::factory()->create(['role' => 'admin_costing']);

        $this->actingAs($user)->get(route('help-center'))
            ->assertOk()
            ->assertSee('Shortcut yang paling berguna')
            ->assertSee('Admin Costing')
            ->assertSee('Draft dan autosave')
            ->assertSee('Penjelasan Deadline, SLA &amp; Aging', false)
            ->assertSee('Service Level Agreement')
            ->assertSee('terlambat 2 hari')
            ->assertSee('Help &amp; Support', false)
            ->assertSee('Help Center')
            ->assertSee('My Tasks')
            ->assertDontSee('Pusat Bantuan');
    }
}
