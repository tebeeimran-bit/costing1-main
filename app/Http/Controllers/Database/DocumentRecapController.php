<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use App\Models\DocumentRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DocumentRecapController extends Controller
{
    public function index(Request $request)
    {
        $revisions = DocumentRevision::with(['project.product'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $businessCategories = $this->businessCategories($revisions);

        $businessCategory = $request->query('business_category')
            ?: ($businessCategories->keys()->first() ?? 'Wiring Harness');

        $customers = $this->customers($revisions, $businessCategory);
        $customer = $request->query('customer')
            ?: ($customers->keys()->first() ?? null);

        $models = $this->models($revisions, $businessCategory, $customer);
        $model = $request->query('model')
            ?: ($models->keys()->first() ?? null);

        $revisionOptions = $this->revisionOptions($revisions, $businessCategory, $customer, $model);
        $revisionId = (int) $request->query('revision_id');

        if ($revisionId <= 0 || !$revisionOptions->has($revisionId)) {
            $revisionId = (int) ($revisionOptions->keys()->first() ?? 0);
        }

        $selectedRevision = $revisionId > 0
            ? $revisions->firstWhere('id', $revisionId)
            : null;

        $documents = $selectedRevision
            ? $this->documentsForRevision($selectedRevision)
            : collect();

        return view('database.document-recap', [
            'businessCategories' => $businessCategories,
            'businessCategory' => $businessCategory,
            'customers' => $customers,
            'customer' => $customer,
            'models' => $models,
            'model' => $model,
            'revisionOptions' => $revisionOptions,
            'revisionId' => $revisionId,
            'selectedRevision' => $selectedRevision,
            'documents' => $documents,
        ]);
    }

    private function businessCategories(Collection $revisions): Collection
    {
        return $revisions
            ->groupBy(fn (DocumentRevision $revision) => $this->businessCategoryName($revision))
            ->sortKeys();
    }

    private function customers(Collection $revisions, ?string $businessCategory): Collection
    {
        return $revisions
            ->filter(fn (DocumentRevision $revision) => $this->businessCategoryName($revision) === $businessCategory)
            ->groupBy(fn (DocumentRevision $revision) => $revision->project->customer ?? '-')
            ->sortKeys();
    }

    private function models(Collection $revisions, ?string $businessCategory, ?string $customer): Collection
    {
        return $revisions
            ->filter(function (DocumentRevision $revision) use ($businessCategory, $customer) {
                return $this->businessCategoryName($revision) === $businessCategory
                    && ($revision->project->customer ?? '-') === $customer;
            })
            ->groupBy(fn (DocumentRevision $revision) => $revision->project->model ?? '-')
            ->sortKeys();
    }

    private function revisionOptions(Collection $revisions, ?string $businessCategory, ?string $customer, ?string $model): Collection
    {
        return $revisions
            ->filter(function (DocumentRevision $revision) use ($businessCategory, $customer, $model) {
                return $this->businessCategoryName($revision) === $businessCategory
                    && ($revision->project->customer ?? '-') === $customer
                    && ($revision->project->model ?? '-') === $model;
            })
            ->sortBy(fn (DocumentRevision $revision) => $revision->version_label ?? $revision->revision_number ?? $revision->id)
            ->mapWithKeys(function (DocumentRevision $revision) {
                return [
                    $revision->id => $revision->version_label
                        ?: ('Rev. ' . str_pad((string) ($revision->revision_number ?? 0), 2, '0', STR_PAD_LEFT)),
                ];
            });
    }

    private function documentsForRevision(DocumentRevision $revision): Collection
    {
        return collect([
            $this->documentRow($revision, 'partlist', 'Partlist', $revision->partlist_file_path ?? null, $revision->partlist_original_name ?? null, $revision->partlist_received_date ?? null, (int) ($revision->partlist_revision_count ?? 0)),
            $this->documentRow($revision, 'umh', 'UMH', $revision->umh_file_path ?? null, $revision->umh_original_name ?? null, $revision->umh_received_date ?? null, (int) ($revision->umh_revision_count ?? 0)),
            $this->documentRow($revision, 'a00', 'RFQ / RFI', $revision->a00_document_file_path ?? null, $revision->a00_document_original_name ?? null, $revision->a00_received_date ?? null, 0),
            $this->documentRow($revision, 'a04', 'Canceled / Failed', $revision->a04_document_file_path ?? null, $revision->a04_document_original_name ?? null, $revision->a04_received_date ?? null, 0),
            $this->documentRow($revision, 'a05', 'Die Go', $revision->a05_document_file_path ?? null, $revision->a05_document_original_name ?? null, $revision->a05_received_date ?? null, 0),
            $this->documentRow($revision, 'summary', 'Costing Summary', null, 'Costing Summary.xlsx', $revision->updated_at ?? $revision->created_at, 0, false),
        ]);
    }

    private function documentRow(
        DocumentRevision $revision,
        string $type,
        string $label,
        ?string $path,
        ?string $name,
        $date,
        int $revisionCount = 0,
        bool $downloadable = true
    ): object {
        $available = !empty($path) || ($type === 'summary' && !empty($name));

        return (object) [
            'type' => $type,
            'label' => $label,
            'name' => $name ?: $this->defaultDocumentName($revision, $type),
            'date' => $date,
            'available' => $available,
            'downloadable' => $downloadable && $available && !empty($path),
            'download_url' => ($downloadable && $available && !empty($path))
                ? route('tracking-documents.download', [$revision->id, $type], false)
                : null,
            'revision_count' => $revisionCount,
            'status_label' => $available ? ($revisionCount > 0 ? 'Revisi ' . $revisionCount . 'x' : 'Tersedia') : 'Belum Upload',
            'status_class' => $available ? ($revisionCount > 0 ? 'orange' : 'green') : 'red',
        ];
    }

    private function defaultDocumentName(DocumentRevision $revision, string $type): string
    {
        $label = match ($type) {
            'partlist' => 'Partlist',
            'umh' => 'UMH',
            'a00' => 'A00 RFQ-RFI',
            'a04' => 'A04 Canceled-Failed',
            'a05' => 'A05 Die Go',
            default => 'Document',
        };

        return $label . '.xlsx';
    }

    private function businessCategoryName(DocumentRevision $revision): string
    {
        return $revision->project->product->name
            ?? $revision->project->product->business_category
            ?? $revision->project->business_category
            ?? 'Wiring Harness';
    }
}
