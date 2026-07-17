<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_read_and_discard_own_draft(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $payload = [
            ['name' => 'period', 'value' => '2026-07', 'type' => 'text', 'checked' => false],
            ['name' => 'forecast', 'value' => '1200', 'type' => 'number', 'checked' => false],
        ];

        $this->actingAs($user)->postJson(route('costing.draft.store'), [
            'draft_key' => 'new',
            'payload' => $payload,
        ])->assertOk()->assertJson(['success' => true]);

        $this->actingAs($user)->getJson(route('costing.draft.show', ['draft_key' => 'new']))
            ->assertOk()
            ->assertJsonPath('draft.payload.0.name', 'period')
            ->assertJsonPath('draft.payload.1.value', '1200');

        $this->actingAs($user)->deleteJson(route('costing.draft.destroy'), ['draft_key' => 'new'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('costing_drafts', ['user_id' => $user->id, 'draft_key' => 'new']);
    }

    public function test_drafts_are_isolated_per_user(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)->postJson(route('costing.draft.store'), [
            'draft_key' => 'new',
            'payload' => [['name' => 'period', 'value' => 'rahasia']],
        ])->assertOk();

        $this->actingAs($other)->getJson(route('costing.draft.show', ['draft_key' => 'new']))
            ->assertOk()
            ->assertJsonPath('draft', null);
    }
}
