<?php

namespace Database\Seeders;

use App\Models\CycleTimeTemplate;
use Illuminate\Database\Seeder;

class CycleTimeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $processes = [
            'Cutting, Stripping, Crimping,Dipping',
            'Cutting, Stripping, Crimping',
            'Cutting, Stripping',
            'Hot Marking',
            'Stripping Cosmic',
            'Pasang sleeve&Seal',
            'Crimping',
            'Twisting',
            'Resistance Welding (Welding)',
            'HF Sealer',
            'Potong tube',
            'Dipping',
            'Middle stripping',
            'Jointing',
            'Solder Joint',
            'Joint Taping',
            'Pasang Water proof Joint/Butyl',
            'shrinkage',
            'QC Preparation',
            'Pasang Clip Coupler',
            'Pasang Dummy Plug',
            'Pasang Cover',
            'Pasang Tube',
            'Pasang Grommet',
            'Housing',
            'Pasang Retainer',
            'Solder',
            'Material Assy/Supply',
            'Setting',
            'Assembling',
            'Potong Clip',
            'Visual 1 + Air Leakage',
            'Checker',
            'Checker Circuit',
            'Pasang FUSE',
            '-',
            'Painting Black',
            'Visual 2',
            'Pre Delivery',
        ];

        CycleTimeTemplate::query()->delete();

        foreach ($processes as $process) {
            CycleTimeTemplate::create([
                'process' => $process,
            ]);
        }
    }
}
