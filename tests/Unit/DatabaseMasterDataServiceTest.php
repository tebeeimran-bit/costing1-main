<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseMasterDataService;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class DatabaseMasterDataServiceTest extends TestCase
{
    public function test_update_trims_selected_string_attributes(): void
    {
        $service = new DatabaseMasterDataService();
        $model = new class extends Model {
            public array $captured = [];

            public function update(array $attributes = [], array $options = []): bool
            {
                $this->captured = $attributes;

                return true;
            }

            public function refresh(): static
            {
                return $this;
            }
        };

        $service->update($model, [
            'code' => '  CUST-01  ',
            'name' => '  Customer One  ',
            'type' => 'keep-as-is',
        ], ['code', 'name']);

        $this->assertSame('CUST-01', $model->captured['code']);
        $this->assertSame('Customer One', $model->captured['name']);
        $this->assertSame('keep-as-is', $model->captured['type']);
    }
}
