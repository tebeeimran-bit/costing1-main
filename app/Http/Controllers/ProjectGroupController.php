<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\MaterialBreakdown;
use App\Services\Project\ProjectWorkflowService;
use App\Services\Project\ProjectCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectGroupController extends Controller
{
    public function index(Request $request, ProjectWorkflowService $workflowService, ProjectCompletenessService $completenessService)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $revisions = DocumentRevision::with([
            'project.product',
            'latestApproval.submitter',
            'latestApproval.approver',
            'latestApproval.rejecter',
            'latestSubmission',
            'unpricedParts:id,document_revision_id,resolved_at',
        ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $costingByRevisionId = CostingData::with(['customer', 'product'])->withCount('materialBreakdowns')
            ->whereNotNull('tracking_revision_id')
            ->get()
            ->keyBy('tracking_revision_id');

        $userRole = (string) optional($request->user())->role;

        $children = $revisions->map(function (DocumentRevision $revision) use ($costingByRevisionId, $userRole, $workflowService, $completenessService) {
            $project = $revision->project;
            $costing = $costingByRevisionId->get($revision->id);
            $latestApproval = $revision->latestApproval;
            $latestSubmission = $revision->latestSubmission;
            $cogmValue = $costing ? $this->cogmValue($costing) : null;
            $workflow = $workflowService->build($revision, $costing, $userRole);
            $completeness = $completenessService->build($revision, $costing);

            $businessCategory = $this->cleanText(
                $project->product->name
                    ?? $project->product->business_category
                    ?? $project->business_category
                    ?? $costing->product->name
                    ?? 'WIRING HARNESS'
            );

            $customer = $this->cleanText(
                $costing->customer->name
                    ?? $project->customer
                    ?? '-'
            );

            $model = $this->normalizeModel(
                $costing->model
                    ?? $project->model
                    ?? '-'
            );

            $partNumber = $this->cleanText(
                $costing->assy_no
                    ?? $project->part_number
                    ?? '-'
            );

            $partName = $this->cleanText(
                $costing->assy_name
                    ?? $project->part_name
                    ?? '-'
            );

            return (object) [
                'revision' => $revision,
                'project' => $project,
                'costing' => $costing,
                'latest_approval' => $latestApproval,
                'latest_submission' => $latestSubmission,
                'cogm_value' => $cogmValue,
                'workflow' => $workflow,
                'completeness' => $completeness,

                'business_category' => $businessCategory,
                'customer' => $customer,
                'model' => $model,

                'part_number' => $partNumber,
                'part_name' => $partName,
                'revision_label' => $revision->version_label
                    ?: ('V' . (string) ($revision->revision_number ?? 0)),
                'revision_count' => (int) ($revision->revision_number ?? 0),
                'pic_engineering' => $this->cleanText(
                    $revision->pic_engineering
                        ?? $project->pic_engineering
                        ?? $project->engineering_pic
                        ?? $costing->pic_engineering
                        ?? '-'
                ),
                'pic_marketing' => $this->cleanText(
                    $revision->pic_marketing
                        ?? $project->pic_marketing
                        ?? $project->marketing_pic
                        ?? $costing->pic_marketing
                        ?? '-'
                ),

                'status_code' => $revision->status,
                'status_label' => $revision->status_label,
                'status_class' => $this->revisionStatusClass($revision),
                'health_messages' => $this->costingHealthMessages($costing),
                'approval_submitter' => $latestApproval?->submitter?->name ?? '-',
                'approval_approver' => $latestApproval?->approver?->name ?? '-',
                'approval_rejecter' => $latestApproval?->rejecter?->name ?? '-',
                'approval_rejection_notes' => $latestApproval?->rejection_notes,
                'approval_submitted_at' => $latestApproval?->submitted_at,
                'approval_approved_at' => $latestApproval?->approved_at,
                'marketing_submitted_at' => $latestSubmission?->submitted_at,
                'can_submit_approval' => in_array($userRole, ['admin', 'admin_costing', 'editor'], true)
                    && $costing
                    && in_array($revision->status, [
                        DocumentRevision::STATUS_SUDAH_COSTING,
                        DocumentRevision::STATUS_COGM_GENERATED,
                        DocumentRevision::STATUS_REJECTED_BY_COORDINATOR,
                    ], true),
                'can_approve_approval' => in_array($userRole, ['admin', 'coordinator_costing'], true)
                    && $revision->status === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
                'can_reject_approval' => in_array($userRole, ['admin', 'coordinator_costing'], true)
                    && $revision->status === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
                'can_send_marketing' => in_array($userRole, ['admin', 'coordinator_costing'], true)
                    && $revision->status === DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,

                'created_at' => $revision->created_at ?? $project->created_at ?? $costing->created_at ?? null,
                'updated_at' => $revision->updated_at ?? $project->updated_at ?? $costing->updated_at ?? null,
            ];
        });

        if ($search !== '') {
            $needle = mb_strtolower($this->cleanText($search));
            $children = $children->filter(function ($item) use ($needle) {
                $text = implode(' ', [
                    $item->business_category,
                    $item->customer,
                    $item->model,
                    $item->part_number,
                    $item->part_name,
                    $item->revision_label,
                    $item->pic_engineering,
                    $item->pic_marketing,
                    $item->status_label,
                    collect($item->health_messages)->pluck('label')->implode(' '),
                ]);

                return str_contains(mb_strtolower($text), $needle);
            })->values();
        }

        $groups = $children
            ->groupBy(fn ($item) => $this->groupKey($item->business_category, $item->customer, $item->model))
            ->map(function (Collection $items) {
                $first = $items->first();
                $sortedItems = $items
                    ->sortBy([
                        ['part_number', 'asc'],
                        ['revision_label', 'asc'],
                    ])
                    ->values();
                $workflowAverage = (int) round($items->avg(fn ($item) => $item->workflow['progress']));
                $workflowCompleted = $items->filter(fn ($item) => $item->workflow['is_complete'])->count();
                $workflowAttention = $items->count() - $workflowCompleted;

                return (object) [
                    'key' => $this->groupKey($first->business_category, $first->customer, $first->model),
                    'business_category' => $first->business_category,
                    'customer' => $first->customer,
                    'model' => $first->model,
                    'project_name' => $this->joinUnique($items->pluck('part_name')),
                    'pic_engineering' => $this->joinUnique($items->pluck('pic_engineering')),
                    'pic_marketing' => $this->joinUnique($items->pluck('pic_marketing')),
                    'created_at' => $items->sortBy('created_at')->first()->created_at,
                    'updated_at' => $items->sortByDesc('updated_at')->first()->updated_at,
                    'total_part_number' => $items->pluck('part_number')->filter()->unique()->count(),
                    'total_items' => $items->count(),
                    'workflow_progress' => $workflowAverage,
                    'workflow_completed' => $workflowCompleted,
                    'workflow_attention' => $workflowAttention,
                    'status_summary' => $items
                        ->groupBy('status_label')
                        ->map(fn (Collection $statusItems) => (object) [
                            'label' => $statusItems->first()->status_label,
                            'class' => $statusItems->first()->status_class,
                            'count' => $statusItems->count(),
                        ])
                        ->values(),
                    'items' => $sortedItems,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedGroups = new LengthAwarePaginator(
            $groups->forPage($currentPage, $perPage)->values(),
            $groups->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('projects.index', [
            'pagedGroups' => $pagedGroups,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    private function revisionStatusClass(DocumentRevision $revision): string
    {
        return match ($revision->status) {
            DocumentRevision::STATUS_PENDING_FORM_INPUT => 'orange',
            DocumentRevision::STATUS_SUDAH_COSTING => 'blue',
            DocumentRevision::STATUS_PENDING_PRICING => 'orange',
            DocumentRevision::STATUS_COGM_GENERATED => 'blue',
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL => 'orange',
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR => 'green',
            DocumentRevision::STATUS_REJECTED_BY_COORDINATOR => 'red',
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING => 'green',
            default => 'green',
        };
    }

    private function cogmValue(CostingData $costing): float
    {
        return (float) ($costing->material_cost ?? 0)
            + (float) ($costing->labor_cost ?? 0)
            + (float) ($costing->overhead_cost ?? 0)
            + (float) ($costing->scrap_cost ?? 0);
    }

    private function costingHealthMessages(?CostingData $costing): array
    {
        if (!$costing) {
            return [];
        }

        $messages = [];

        $materialRows = MaterialBreakdown::query()
            ->where('costing_data_id', $costing->id)
            ->get(['amount1', 'unit_price_basis', 'cn_type']);

        $missingMaterialCount = $materialRows->filter(function ($row) {
            return (float) ($row->amount1 ?? 0) <= 0
                && (float) ($row->unit_price_basis ?? 0) <= 0;
        })->count();

        $hasEstimateMaterialPrice = $materialRows->contains(function ($row) {
            return strtoupper(trim((string) ($row->cn_type ?? ''))) === 'E';
        });

        $processCostIsEmpty = (float) ($costing->labor_cost ?? 0) <= 0;

        if ($missingMaterialCount > 0) {
            $messages[] = [
                'type' => 'danger',
                'label' => $missingMaterialCount . ' part belum ada harga',
            ];
        }

        if ($hasEstimateMaterialPrice) {
            $messages[] = [
                'type' => 'warning',
                'label' => 'Ada harga estimate',
            ];
        }

        if ($processCostIsEmpty) {
            $messages[] = [
                'type' => 'info',
                'label' => 'Process cost belum ada',
            ];
        }

        return $messages;
    }

    private function groupKey(string $businessCategory, string $customer, string $model): string
    {
        return implode('|', [
            $this->normalizeKey($businessCategory),
            $this->normalizeKey($customer),
            $this->normalizeKey($model),
        ]);
    }

    private function normalizeModel(?string $value): string
    {
        return strtoupper($this->cleanText((string) $value));
    }

    private function normalizeKey(?string $value): string
    {
        $value = mb_strtolower($this->cleanText((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function cleanText(?string $value): string
    {
        $value = (string) $value;
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value) !== '' ? trim($value) : '-';
    }

    private function joinUnique(Collection $values): string
    {
        $filtered = $values
            ->filter(fn ($value) => filled($value) && $value !== '-')
            ->map(fn ($value) => $this->cleanText((string) $value))
            ->unique()
            ->values();

        if ($filtered->isEmpty()) {
            return '-';
        }

        if ($filtered->count() > 3) {
            return $filtered->take(3)->implode(', ') . ' +' . ($filtered->count() - 3);
        }

        return $filtered->implode(', ');
    }
}
