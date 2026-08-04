<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_blank_source_uses_manual_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('rate-kurs.store'), [
            'period_date' => '2026-07-01',
            'usd_to_idr' => 17923,
            'jpy_to_idr' => 111.59,
            'lme_copper' => 13574,
            'source' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('exchange_rates', [
            'period_date' => '2026-07-01 00:00:00',
            'source' => 'Manual',
        ]);
    }

    public function test_admin_can_update_an_existing_exchange_rate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rate = ExchangeRate::create([
            'period_date' => '2026-07-01',
            'usd_to_idr' => 17000,
            'jpy_to_idr' => 110,
            'lme_copper' => 13000,
            'source' => 'Manual',
        ]);

        $this->actingAs($admin)->put(route('rate-kurs.update', $rate->id), [
            'period_date' => '2026-08-01',
            'usd_to_idr' => 17923,
            'jpy_to_idr' => 111.59,
            'lme_copper' => 13574,
            'source' => 'Bank Indonesia',
        ])->assertRedirect();

        $this->assertDatabaseHas('exchange_rates', [
            'id' => $rate->id,
            'lme_copper' => 13574,
            'source' => 'Bank Indonesia',
        ]);
        $this->assertDatabaseCount('exchange_rates', 1);
    }

    public function test_costing_form_lists_exchange_rates_and_allows_manual_option(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ExchangeRate::create([
            'period_date' => '2026-08-01',
            'usd_to_idr' => 17923,
            'jpy_to_idr' => 111.59,
            'lme_copper' => 13574,
            'source' => 'Bank Indonesia',
        ]);

        $this->actingAs($admin)->get(route('form'))
            ->assertOk()
            ->assertSee('Input manual')
            ->assertSee('USD: Rp 17.923')
            ->assertSee('LME: Rp 13.574');
    }

    public function test_selected_exchange_rate_remains_selected_after_refresh(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rate = ExchangeRate::create([
            'period_date' => '2026-08-01',
            'usd_to_idr' => 17923,
            'jpy_to_idr' => 111.59,
            'lme_copper' => 13574,
            'source' => 'Bank Indonesia',
        ]);
        $customer = Customer::create(['name' => 'Astra', 'code' => 'ASTR']);
        $category = BusinessCategory::create(['name' => 'Wiring Harness', 'code' => 'WH']);
        $product = Product::create(['name' => 'Wiring Harness', 'code' => 'WH', 'line' => $category->code]);
        $costing = CostingData::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'period' => '2026-08',
            'exchange_rate_id' => $rate->id,
            'exchange_rate_usd' => 17923,
            'exchange_rate_jpy' => 111.59,
            'lme_rate' => 13574,
        ]);

        $this->actingAs($admin)->get(route('form', ['id' => $costing->id]))
            ->assertOk()
            ->assertSee('value="' . $rate->id . '"', false)
            ->assertSee('data-lme="13574.00"', false)
            ->assertSee('selected', false);
    }

    public function test_dropdown_selection_is_remembered_immediately_without_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rate = ExchangeRate::create([
            'period_date' => '2026-07-01',
            'usd_to_idr' => 17923,
            'jpy_to_idr' => 111.59,
            'lme_copper' => 13574,
            'source' => 'Manual',
        ]);

        $this->actingAs($admin)->postJson(route('costing.selected-exchange-rate'), [
            'exchange_rate_id' => $rate->id,
            'selection_key' => 'new',
        ])->assertOk()->assertJsonPath('success', true);

        $response = $this->actingAs($admin)->get(route('form'));
        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<option value="' . $rate->id . '"[^>]*selected>/',
            $response->getContent()
        );
    }
}
