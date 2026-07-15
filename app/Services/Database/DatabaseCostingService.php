<?php

namespace App\Services\Database;

use App\Models\CostingData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DatabaseCostingService
{
    public function getCostingPageData(Request $request): array
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'customer' => trim((string) $request->query('customer', '')),
            'period' => trim((string) $request->query('period', '')),
            'line' => trim((string) $request->query('line', '')),
            'assy_no' => trim((string) $request->query('assy_no', '')),
            'revisi' => trim((string) $request->query('revisi', '')),
        ];

        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $query = CostingData::query()
            ->with(['customer', 'product', 'trackingRevision'])
            ->withCount('unpricedParts');

        if ($filters['search'] !== '') {
            $keyword = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('assy_no', 'like', $keyword)
                    ->orWhere('assy_name', 'like', $keyword)
                    ->orWhere('model', 'like', $keyword)
                    ->orWhere('part_number', 'like', $keyword)
                    ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('name', 'like', $keyword))
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('name', 'like', $keyword)
                            ->orWhere('line', 'like', $keyword);
                    });
            });
        }

        if ($filters['customer'] !== '') {
            $query->whereHas('customer', fn (Builder $builder) => $builder->where('name', $filters['customer']));
        }

        if ($filters['period'] !== '') {
            $query->where('period', $filters['period']);
        }

        if ($filters['line'] !== '') {
            $query->whereHas('product', function (Builder $builder) use ($filters) {
                $builder->where('line', $filters['line'])
                    ->orWhere('name', $filters['line']);
            });
        }

        if ($filters['assy_no'] !== '') {
            $query->where('assy_no', 'like', '%' . $filters['assy_no'] . '%');
        }

        if ($filters['revisi'] !== '') {
            $revisionNumber = null;

            if (preg_match('/\d+/', $filters['revisi'], $matches)) {
                $revisionNumber = (int) $matches[0] + 1;
            }

            if ($revisionNumber !== null) {
                $query->whereHas('trackingRevision', function (Builder $revisionQuery) use ($revisionNumber) {
                    $revisionQuery->where('version_number', $revisionNumber);
                });
            }
        }

        $costingData = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return compact('costingData', 'filters', 'perPage');
    }

    public function getMaterialCostPageData(Request $request): array
    {
        $period = trim((string) $request->input('period', 'all'));

        $assyExpr = "COALESCE(NULLIF(costing_data.assy_no, ''), '-')";
        $modelExpr = "COALESCE(NULLIF(costing_data.model, ''), '-')";
        $customerExpr = "COALESCE(NULLIF(customers.name, ''), '-')";
        $businessCategoryExpr = "COALESCE(NULLIF(products.line, ''), COALESCE(NULLIF(products.name, ''), 'Uncategorized'))";

        $query = CostingData::query()
            ->leftJoin('customers', 'customers.id', '=', 'costing_data.customer_id')
            ->leftJoin('products', 'products.id', '=', 'costing_data.product_id')
            ->selectRaw("{$assyExpr} as assy_no")
            ->selectRaw("{$modelExpr} as model")
            ->selectRaw("{$customerExpr} as customer_name")
            ->selectRaw("{$businessCategoryExpr} as business_category")
            ->selectRaw('SUM(COALESCE(costing_data.material_cost, 0)) as material_cost_total')
            ->selectRaw('COUNT(*) as project_count');

        if ($period !== '' && $period !== 'all') {
            $query->where('costing_data.period', $period);
        }

        $materialCostRows = $query
            ->groupByRaw("{$assyExpr}, {$modelExpr}, {$customerExpr}, {$businessCategoryExpr}")
            ->orderByRaw("{$assyExpr} asc")
            ->orderByRaw("{$modelExpr} asc")
            ->orderByRaw("{$customerExpr} asc")
            ->orderByRaw("{$businessCategoryExpr} asc")
            ->get();

        $totalMaterialCost = (float) $materialCostRows->sum('material_cost_total');
        $totalProjects = (int) $materialCostRows->sum('project_count');

        $periodOptions = CostingData::query()
            ->select('period')
            ->whereNotNull('period')
            ->where('period', '!=', '')
            ->distinct()
            ->orderBy('period', 'desc')
            ->pluck('period')
            ->values();

        return compact(
            'materialCostRows',
            'totalMaterialCost',
            'totalProjects',
            'periodOptions',
            'period'
        );
    }

    public function delete(CostingData $costing): void
    {
        $revision = $costing->trackingRevision;

        \Illuminate\Support\Facades\DB::transaction(function () use ($costing, $revision) {
            if ($revision) {
                $revision->unpricedParts()->delete();
            }

            $costing->delete();

            if ($revision) {
                $revision->update([
                    'status' => \App\Models\DocumentRevision::STATUS_PENDING_FORM_INPUT,
                    'cogm_generated_at' => null,
                ]);
            }
        });
    }
}
