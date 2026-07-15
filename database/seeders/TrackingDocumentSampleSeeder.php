<?php

namespace Database\Seeders;

use App\Models\CogmSubmission;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TrackingDocumentSampleSeeder extends Seeder
{
    public function run(): void
    {
        $sampleProjects = [
            [
                'project' => [
                    'customer' => 'Astra Honda Motor, PT',
                    'model' => 'K4MA',
                    'part_number' => '32100-K4MA-W203',
                    'part_name' => 'HARNESS WIRE',
                    'project_key' => hash('sha256', 'sample|astra honda motor|k4ma|32100-k4ma-w203|harness wire'),
                ],
                'revisions' => [
                    [
                        'version_number' => 1,
                        'received_date' => '2025-08-11',
                        'pic_engineering' => 'FERDINANDUS VARANI ANUGRAH PERDANA',
                        'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
                        'cogm_generated_at' => '2025-08-12 10:00:00',
                        'pic_marketing' => 'DWI DARNINGSIH',
                        'notes' => 'Initial release from engineering.',
                        'submissions' => [
                            [
                                'submitted_at' => '2025-08-13 09:30:00',
                                'pic_marketing' => 'DWI DARNINGSIH',
                                'cogm_value' => 42850.75,
                                'submitted_by' => 'TIM COSTING A',
                                'notes' => 'COGM V1 submitted to marketing.',
                            ],
                        ],
                    ],
                    [
                        'version_number' => 2,
                        'received_date' => '2025-08-18',
                        'pic_engineering' => 'FERDINANDUS VARANI ANUGRAH PERDANA',
                        'status' => DocumentRevision::STATUS_COGM_GENERATED,
                        'cogm_generated_at' => '2025-08-19 15:20:00',
                        'pic_marketing' => null,
                        'notes' => 'Engineering revision after first costing submit.',
                        'submissions' => [],
                    ],
                ],
            ],
            [
                'project' => [
                    'customer' => 'BYD Motor Indonesia, PT',
                    'model' => 'SA6',
                    'part_number' => 'HA2HE-3721010',
                    'part_name' => 'TWEETER (LOUD - TONE HORN)',
                    'project_key' => hash('sha256', 'sample|byd motor indonesia|sa6|ha2he-3721010|tweeter loud tone horn'),
                ],
                'revisions' => [
                    [
                        'version_number' => 1,
                        'received_date' => '2025-08-12',
                        'pic_engineering' => 'AGUSTINUS WISNU SETIAWAN',
                        'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
                        'cogm_generated_at' => null,
                        'pic_marketing' => null,
                        'notes' => 'Waiting for costing calculation.',
                        'submissions' => [],
                    ],
                ],
            ],
        ];

        foreach ($sampleProjects as $data) {
            $project = DocumentProject::updateOrCreate(
                ['project_key' => $data['project']['project_key']],
                $data['project']
            );

            foreach ($data['revisions'] as $revisionData) {
                $partlistPath = 'tracking-documents/partlist/sample-' . $project->id . '-v' . $revisionData['version_number'] . '.xlsx';
                $umhPath = 'tracking-documents/umh/sample-' . $project->id . '-v' . $revisionData['version_number'] . '.xlsx';

                if (!Storage::exists($partlistPath)) {
                    Storage::put($partlistPath, "Sample Partlist data for {$project->part_number} V{$revisionData['version_number']}");
                }

                if (!Storage::exists($umhPath)) {
                    Storage::put($umhPath, "Sample UMH data for {$project->part_number} V{$revisionData['version_number']}");
                }

                $revision = DocumentRevision::updateOrCreate(
                    [
                        'document_project_id' => $project->id,
                        'version_number' => $revisionData['version_number'],
                    ],
                    [
                        'received_date' => $revisionData['received_date'],
                        'pic_engineering' => $revisionData['pic_engineering'],
                        'status' => $revisionData['status'],
                        'cogm_generated_at' => $revisionData['cogm_generated_at'],
                        'pic_marketing' => $revisionData['pic_marketing'],
                        'partlist_original_name' => strtoupper($project->model) . '-PARTLIST-V' . $revisionData['version_number'] . '.xlsx',
                        'partlist_file_path' => $partlistPath,
                        'umh_original_name' => strtoupper($project->model) . '-UMH-V' . $revisionData['version_number'] . '.xlsx',
                        'umh_file_path' => $umhPath,
                        'notes' => $revisionData['notes'],
                    ]
                );

                CogmSubmission::where('document_revision_id', $revision->id)->delete();

                foreach ($revisionData['submissions'] as $submission) {
                    CogmSubmission::create([
                        'document_revision_id' => $revision->id,
                        'submitted_at' => $submission['submitted_at'],
                        'pic_marketing' => $submission['pic_marketing'],
                        'cogm_value' => $submission['cogm_value'],
                        'submitted_by' => $submission['submitted_by'],
                        'notes' => $submission['notes'],
                    ]);
                }
            }
        }
    }
}
