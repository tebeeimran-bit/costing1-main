<?php

namespace App\Services\TrackingDocument;

use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TrackingDocumentProjectService
{
    public function __construct(
        private readonly TrackingDocumentFileService $fileService,
    ) {
    }

    public function createReceipt(array $validated, Request $request): DocumentRevision
    {
        return DB::transaction(function () use ($validated, $request) {
            $resolvedProduct = !empty($validated['business_category_id'])
                ? $this->resolveProductFromBusinessCategoryId((int) $validated['business_category_id'])
                : Product::findOrFail((int) $validated['product_id']);

            $customer = Customer::findOrFail((int) $validated['customer_id']);
            $customerName = trim((string) $customer->name);
            $a00Status = $validated['a00_status'] ?? 'belum_ada';
            $a04Status = $validated['a04_status'] ?? 'belum_ada';
            $a05Status = $validated['a05_status'] ?? 'belum_ada';

            $projectKey = $this->makeProjectKey(
                $customerName,
                (string) $validated['model'],
                (string) $validated['assy_no'],
                (string) $validated['assy_name']
            );

            $project = DocumentProject::firstOrCreate(
                ['project_key' => $projectKey],
                [
                    'product_id' => $resolvedProduct->id,
                    'customer' => $customerName,
                    'model' => $validated['model'],
                    'part_number' => $validated['assy_no'],
                    'part_name' => $validated['assy_name'],
                ]
            );

            if ((int) ($project->product_id ?? 0) !== (int) $resolvedProduct->id) {
                $project->update(['product_id' => $resolvedProduct->id]);
            }

            $nextVersion = (int) $project->revisions()->max('version_number') + 1;

            $a00Document = ($a00Status === 'ada' && $request->hasFile('a00_document_file'))
                ? $this->fileService->storeUploadedFile($request->file('a00_document_file'), 'a00')
                : ['path' => null, 'name' => null];
            $a04Document = ($a04Status === 'ada' && $request->hasFile('a04_document_file'))
                ? $this->fileService->storeUploadedFile($request->file('a04_document_file'), 'a04')
                : ['path' => null, 'name' => null];
            $a05Document = ($a05Status === 'ada' && $request->hasFile('a05_document_file'))
                ? $this->fileService->storeUploadedFile($request->file('a05_document_file'), 'a05')
                : ['path' => null, 'name' => null];
            $partlistDocument = $request->hasFile('partlist_file')
                ? $this->fileService->storeUploadedFile($request->file('partlist_file'), 'partlist')
                : ['path' => '', 'name' => ''];
            $umhDocument = $request->hasFile('umh_file')
                ? $this->fileService->storeUploadedFile($request->file('umh_file'), 'umh')
                : ['path' => '', 'name' => ''];

            return DocumentRevision::create([
                'document_project_id' => $project->id,
                'version_number' => $nextVersion,
                'received_date' => $validated['received_date'] ?? now()->toDateString(),
                'pic_engineering' => $validated['pic_engineering'],
                'pic_marketing' => $validated['pic_marketing'] ?? null,
                'a00' => $a00Status,
                'a00_received_date' => $a00Status === 'ada' ? ($validated['a00_received_date'] ?? null) : null,
                'a00_document_original_name' => $a00Status === 'ada' ? $a00Document['name'] : null,
                'a00_document_file_path' => $a00Status === 'ada' ? $a00Document['path'] : null,
                'a04' => $a04Status,
                'a04_received_date' => $a04Status === 'ada' ? ($validated['a04_received_date'] ?? null) : null,
                'a04_document_original_name' => $a04Status === 'ada' ? $a04Document['name'] : null,
                'a04_document_file_path' => $a04Status === 'ada' ? $a04Document['path'] : null,
                'a05' => $a05Status,
                'a05_received_date' => $a05Status === 'ada' ? ($validated['a05_received_date'] ?? null) : null,
                'a05_document_original_name' => $a05Status === 'ada' ? $a05Document['name'] : null,
                'a05_document_file_path' => $a05Status === 'ada' ? $a05Document['path'] : null,
                'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
                'partlist_original_name' => $partlistDocument['name'],
                'partlist_file_path' => $partlistDocument['path'],
                'partlist_update_count' => 0,
                'partlist_updated_at' => null,
                'umh_original_name' => $umhDocument['name'],
                'umh_file_path' => $umhDocument['path'],
                'umh_update_count' => 0,
                'umh_updated_at' => null,
                'notes' => $validated['notes'] ?? null,
                'change_remark' => $nextVersion === 1
                    ? 'Dokumen awal diterima (baseline V0).'
                    : ($validated['change_remark'] ?? 'Revisi Engineering diterima. Detail perubahan belum diisi.'),
            ]);
        });
    }

    public function addVersion(DocumentRevision $revision): DocumentRevision
    {
        return DB::transaction(function () use ($revision) {
            $project = $revision->project()->lockForUpdate()->firstOrFail();
            $baseRevision = $project->revisions()
                ->orderByDesc('version_number')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->firstOrFail();
            $nextVersion = (int) $project->revisions()->max('version_number') + 1;

            return DocumentRevision::create([
                'document_project_id' => $project->id,
                'version_number' => $nextVersion,
                'received_date' => now()->toDateString(),
                'pic_engineering' => $baseRevision->pic_engineering,
                'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
                'pic_marketing' => $baseRevision->pic_marketing,
                'a00' => $baseRevision->a00,
                'a00_received_date' => $baseRevision->a00_received_date,
                'a00_document_original_name' => $baseRevision->a00_document_original_name,
                'a00_document_file_path' => $baseRevision->a00_document_file_path,
                'a04' => $baseRevision->a04,
                'a04_received_date' => $baseRevision->a04_received_date,
                'a04_document_original_name' => $baseRevision->a04_document_original_name,
                'a04_document_file_path' => $baseRevision->a04_document_file_path,
                'a05' => $baseRevision->a05,
                'a05_received_date' => $baseRevision->a05_received_date,
                'a05_document_original_name' => $baseRevision->a05_document_original_name,
                'a05_document_file_path' => $baseRevision->a05_document_file_path,
                'partlist_original_name' => $baseRevision->partlist_original_name,
                'partlist_file_path' => $baseRevision->partlist_file_path,
                'partlist_update_count' => 0,
                'partlist_updated_at' => null,
                'umh_original_name' => $baseRevision->umh_original_name,
                'umh_file_path' => $baseRevision->umh_file_path,
                'umh_update_count' => 0,
                'umh_updated_at' => null,
                'notes' => $baseRevision->notes,
                'change_remark' => 'Revisi Engineering diterima. Versi baru dibuat.',
            ]);
        });
    }

    public function updateProjectInfo(DocumentProject $project, array $validated, Request $request): array
    {
        $customer = Customer::findOrFail((int) $validated['customer_id']);
        $normalizedCustomer = trim((string) $customer->name);
        $normalizedModel = trim((string) $validated['model']);
        $normalizedPartNumber = trim((string) $validated['part_number']);
        $normalizedPartName = trim((string) $validated['part_name']);
        $nextProjectKey = $this->makeProjectKey(
            $normalizedCustomer,
            $normalizedModel,
            $normalizedPartNumber,
            $normalizedPartName
        );

        $duplicateExists = DocumentProject::query()
            ->where('project_key', $nextProjectKey)
            ->where('id', '!=', $project->id)
            ->exists();

        if ($duplicateExists) {
            return ['updated' => false, 'reason' => 'duplicate'];
        }

        DB::transaction(function () use ($project, $validated, $request, $normalizedCustomer, $normalizedModel, $normalizedPartNumber, $normalizedPartName, $nextProjectKey) {
            $resolvedProduct = !empty($validated['business_category_id'])
                ? $this->resolveProductFromBusinessCategoryId((int) $validated['business_category_id'])
                : Product::findOrFail((int) $validated['product_id']);

            $project->update([
                'product_id' => $resolvedProduct->id,
                'customer' => $normalizedCustomer,
                'model' => $normalizedModel,
                'part_number' => $normalizedPartNumber,
                'part_name' => $normalizedPartName,
                'project_key' => $nextProjectKey,
            ]);

            $latestRevision = $project->revisions()->orderByDesc('version_number')->orderByDesc('id')->first();
            if (! $latestRevision) {
                return;
            }

            $a00 = $this->resolveDocumentUpdate($latestRevision, $request, $validated, 'a00');
            $a04 = $this->resolveDocumentUpdate($latestRevision, $request, $validated, 'a04');
            $a05 = $this->resolveDocumentUpdate($latestRevision, $request, $validated, 'a05');

            $latestRevision->update([
                'received_date' => $validated['received_date'] ?? $latestRevision->received_date,
                'pic_engineering' => $validated['pic_engineering'],
                'pic_marketing' => $validated['pic_marketing'],
                'a00' => $a00['status'],
                'a00_document_original_name' => $a00['name'],
                'a00_document_file_path' => $a00['path'],
                'a04' => $a04['status'],
                'a04_document_original_name' => $a04['name'],
                'a04_document_file_path' => $a04['path'],
                'a05' => $a05['status'],
                'a05_document_original_name' => $a05['name'],
                'a05_document_file_path' => $a05['path'],
            ]);

            /*
             * Sinkronkan informasi project di modal Project ke data Form Costing.
             * Jadi setelah field Quantity / Plant / Periode di Form Costing berubah,
             * modal Edit Project membaca nilai yang sama, dan jika modal disimpan
             * nilainya juga ikut diperbarui ke CostingData.
             */
            $costingData = CostingData::query()
                ->where('tracking_revision_id', $latestRevision->id)
                ->latest('id')
                ->first();

            if ($costingData) {
                $costingColumns = array_fill_keys(Schema::getColumnListing('costing_data'), true);

                $costingPayload = [
                    'product_id' => $resolvedProduct->id,
                    'customer_id' => (int) $validated['customer_id'],
                    'model' => $normalizedModel,
                    'assy_no' => $normalizedPartNumber,
                    'assy_name' => $normalizedPartName,
                    'forecast' => $this->parseNumericInput($validated['forecast'] ?? $costingData->forecast ?? 0),
                    'forecast_uom' => $validated['forecast_uom'] ?? $costingData->forecast_uom ?? 'PCE',
                    'forecast_basis' => $validated['forecast_basis'] ?? $costingData->forecast_basis ?? 'per_month',
                    'project_period' => $this->parseNumericInput($validated['project_period'] ?? $costingData->project_period ?? 0),
                    'line' => $validated['line'] ?? $costingData->line ?? null,
                    'period' => $validated['period'] ?? $costingData->period ?? null,
                ];

                $costingData->update(array_intersect_key($costingPayload, $costingColumns));
            }
        });

        return ['updated' => true];
    }

    public function deleteVersion(DocumentRevision $revision): array
    {
        return DB::transaction(function () use ($revision) {
            $project = $revision->project()->lockForUpdate()->firstOrFail();
            $targetRevision = $project->revisions()->whereKey($revision->id)->lockForUpdate()->first();

            if (! $targetRevision) {
                return ['deleted' => false, 'reason' => 'not_found'];
            }

            $revisionCount = $project->revisions()->lockForUpdate()->count();
            if ($revisionCount <= 1) {
                return ['deleted' => false, 'reason' => 'last_version'];
            }

            $filePaths = $this->fileService->collectRevisionFilePaths($targetRevision);
            $versionLabel = $targetRevision->version_label;
            $targetRevision->delete();

            foreach ($filePaths as $path) {
                $this->fileService->deletePathIfUnused((string) $path);
            }

            return ['deleted' => true, 'version_label' => $versionLabel];
        });
    }

    private function resolveDocumentUpdate(DocumentRevision $revision, Request $request, array $validated, string $type): array
    {
        $status = $validated[$type] ?? ($revision->{$type} ?: 'belum_ada');
        $pathField = $type . '_document_file_path';
        $nameField = $type . '_document_original_name';
        $path = $revision->{$pathField};
        $name = $revision->{$nameField};

        if ($status !== 'ada') {
            return ['status' => 'belum_ada', 'path' => null, 'name' => null];
        }

        $inputName = $type . '_document_file';
        if ($request->hasFile($inputName)) {
            $stored = $this->fileService->storeUploadedFile($request->file($inputName), $type);
            $path = $stored['path'];
            $name = $stored['name'];
        }

        return ['status' => 'ada', 'path' => $path, 'name' => $name];
    }

    private function parseNumericInput($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(['Rp', 'rp', 'IDR', 'idr', ' '], '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function makeProjectKey(string $customer, string $model, string $partNumber, string $partName): string
    {
        $raw = collect([$customer, $model, $partNumber, $partName])
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->implode('|');

        return hash('sha256', $raw);
    }

    private function resolveProductFromBusinessCategoryId(int $businessCategoryId): Product
    {
        $businessCategory = BusinessCategory::findOrFail($businessCategoryId);
        $code = trim((string) $businessCategory->code);
        $name = trim((string) $businessCategory->name);

        $product = Product::firstOrCreate(['code' => $code], ['name' => $name]);
        if (trim((string) $product->name) !== $name) {
            $product->update(['name' => $name]);
        }

        return $product;
    }
}
