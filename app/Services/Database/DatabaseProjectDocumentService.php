<?php

namespace App\Services\Database;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseProjectDocumentService
{
    public function getPageData(Request $request): array
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', ''));
        $perPage = (int) $request->input('per_page', 15);

        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        /*
         * IMPORTANT:
         * Sebelumnya halaman Database Dokumen Project mengambil data dari CostingData.
         * Akibatnya project yang masih PENDING FORM COSTING tidak tampil.
         *
         * Sekarang sumber utama adalah DocumentRevision, supaya semua project tracking
         * tampil, baik yang sudah punya costing_data maupun yang belum.
         */
        $revisions = DocumentRevision::with(['project'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $costingByRevisionId = CostingData::with(['customer', 'product'])
            ->whereNotNull('tracking_revision_id')
            ->get()
            ->keyBy('tracking_revision_id');

        $counts = [
            'a00Count' => 0,
            'a04Count' => 0,
            'a05Count' => 0,
            'partlistMasukCount' => 0,
            'belumPartlistCount' => 0,
            'revisiPartlistCount' => 0,
            'umhMasukCount' => 0,
            'belumUmhCount' => 0,
            'revisiUmhCount' => 0,
        ];

        $rows = $revisions
            ->map(function (DocumentRevision $revision) use (&$counts, $costingByRevisionId) {
                $project = $revision->project;
                $costingData = $costingByRevisionId->get($revision->id);

                $status = 'none';

                if (($revision->a05 ?? null) === 'ada') {
                    $status = 'a05';
                    $counts['a05Count']++;
                } elseif (($revision->a04 ?? null) === 'ada') {
                    $status = 'a04';
                    $counts['a04Count']++;
                } elseif (($revision->a00 ?? null) === 'ada') {
                    $status = 'a00';
                    $counts['a00Count']++;
                }

                if (($revision->partlist ?? null) === 'ada' || !empty($revision->partlist_file_path)) {
                    $counts['partlistMasukCount']++;
                } else {
                    $counts['belumPartlistCount']++;
                }

                if (($revision->umh ?? null) === 'ada' || !empty($revision->umh_file_path)) {
                    $counts['umhMasukCount']++;
                } else {
                    $counts['belumUmhCount']++;
                }

                $counts['revisiPartlistCount'] += (int) ($revision->partlist_revision_count ?? 0);
                $counts['revisiUmhCount'] += (int) ($revision->umh_revision_count ?? 0);

                /*
                 * View lama memakai $row->costingData->customer->name,
                 * $row->costingData->model, dan $row->costingData->assy_name.
                 * Untuk revision yang belum punya CostingData, kita sediakan fallback
                 * object agar view tetap aman tanpa perlu ubah blade.
                 */
                if (!$costingData) {
                    $costingData = (object) [
                        'id' => null,
                        'customer' => (object) [
                            'name' => $project->customer ?? null,
                        ],
                        'product' => $project->product ?? null,
                        'model' => $project->model ?? null,
                        'assy_no' => $project->part_number ?? null,
                        'assy_name' => $project->part_name ?? null,
                    ];
                }

                return (object) [
                    'revision' => $revision,
                    'project' => $project,
                    'costingData' => $costingData,
                    'status' => $status,
                ];
            })
            ->filter(fn ($row) => $row->revision && $row->project)
            ->values();

        $rows = $this->filterRows($rows, $search, $statusFilter);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedRows = new LengthAwarePaginator(
            $rows->forPage($currentPage, $perPage),
            $rows->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return array_merge($counts, compact('pagedRows', 'search', 'statusFilter', 'perPage'));
    }

    public function update(DocumentRevision $revision, array $validated, Request $request): void
    {
        foreach (['a00', 'a04', 'a05'] as $prefix) {
            $status = $validated[$prefix];
            $revision->$prefix = $status;

            $dateField = $prefix . '_received_date';
            $fileField = $prefix . '_document_file';
            $pathField = $prefix . '_document_file_path';
            $nameField = $prefix . '_document_original_name';

            if ($status === 'ada') {
                $revision->$dateField = $validated[$dateField] ?? null;

                if ($prefix === 'a04') {
                    $revision->a04_reason = trim((string) ($validated['a04_reason'] ?? ''));
                }

                if ($request->hasFile($fileField)) {
                    $oldPath = $revision->$pathField;
                    $file = $request->file($fileField);
                    $path = $file->storeAs(
                        'tracking-documents/' . $prefix,
                        now()->format('YmdHis') . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
                    );

                    $revision->$pathField = $path;
                    $revision->$nameField = $file->getClientOriginalName();

                    if (!empty($oldPath)) {
                        Storage::delete($oldPath);
                    }
                }
            } else {
                $this->deleteStoredDocument($revision->$pathField);
                $revision->$dateField = null;
                $revision->$pathField = null;
                $revision->$nameField = null;

                if ($prefix === 'a04') {
                    $revision->a04_reason = null;
                }
            }
        }

        foreach (['partlist', 'umh'] as $prefix) {
            $status = $validated[$prefix] ?? 'belum_ada';
            $revision->$prefix = $status;

            $dateField = $prefix . '_received_date';
            $fileField = $prefix . '_document_file';
            $pathField = $prefix . '_file_path';
            $nameField = $prefix . '_original_name';
            $revisionCountField = $prefix . '_revision_count';

            if ($status === 'ada') {
                $revision->$dateField = $validated[$dateField] ?? null;
                $revision->$revisionCountField = (int) ($validated[$revisionCountField] ?? 0);

                if ($request->hasFile($fileField)) {
                    $oldPath = $revision->$pathField;
                    $file = $request->file($fileField);
                    $path = $file->storeAs(
                        'tracking-documents/' . $prefix,
                        now()->format('YmdHis') . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension()
                    );

                    $revision->$pathField = $path;
                    $revision->$nameField = $file->getClientOriginalName();

                    if (!empty($oldPath)) {
                        Storage::delete($oldPath);
                    }
                }
            } else {
                $this->deleteStoredDocument($revision->$pathField);
                $revision->$dateField = null;
                $revision->$pathField = null;
                $revision->$nameField = null;
                $revision->$revisionCountField = 0;
            }
        }

        $revision->save();
    }


    public function destroy(DocumentRevision $revision): void
    {
        foreach (['a00', 'a04', 'a05'] as $prefix) {
            $this->deleteStoredDocument($revision->{$prefix . '_document_file_path'});

            $revision->$prefix = null;
            $revision->{$prefix . '_received_date'} = null;
            $revision->{$prefix . '_document_original_name'} = null;
            $revision->{$prefix . '_document_file_path'} = null;

            if ($prefix === 'a04') {
                $revision->a04_reason = null;
            }
        }

        foreach (['partlist', 'umh'] as $prefix) {
            $this->deleteStoredDocument($revision->{$prefix . '_file_path'});

            $revision->$prefix = 'belum_ada';
            $revision->{$prefix . '_received_date'} = null;
            $revision->{$prefix . '_original_name'} = null;
            $revision->{$prefix . '_file_path'} = null;
            $revision->{$prefix . '_revision_count'} = 0;
        }

        $revision->save();
    }


    private function filterRows(Collection $rows, string $search, string $statusFilter): Collection
    {
        if ($search !== '') {
            $searchLower = mb_strtolower($search);

            $rows = $rows->filter(function ($row) use ($searchLower) {
                $text = implode(' ', array_filter([
                    $row->costingData->customer->name ?? '',
                    $row->costingData->model ?? '',
                    $row->costingData->assy_name ?? '',
                    $row->costingData->assy_no ?? '',
                    $row->project->customer ?? '',
                    $row->project->model ?? '',
                    $row->project->part_number ?? '',
                    $row->project->part_name ?? '',
                    $row->revision->version_label ?? '',
                    $row->revision->status_label ?? '',
                    $row->revision->partlist_original_name ?? '',
                    $row->revision->umh_original_name ?? '',
                ]));

                return str_contains(mb_strtolower($text), $searchLower);
            })->values();
        }

        if ($statusFilter !== '') {
            $rows = $rows->filter(fn ($row) => $row->status === $statusFilter)->values();
        }

        return $rows;
    }

    private function deleteStoredDocument(?string $path): void
    {
        if (!empty($path)) {
            Storage::delete($path);
        }
    }
}
