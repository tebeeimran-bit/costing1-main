<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed only the minimum records required by a fresh installation.
     * Project, customer, material, and costing records must come from the
     * real application workflow, never from demonstration data.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CycleTimeTemplateSeeder::class,
        ]);
    }
}
