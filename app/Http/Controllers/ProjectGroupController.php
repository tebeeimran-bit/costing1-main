<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\DocumentProject;
use App\Models\DocumentControlRegistration;
use App\Models\MaterialBreakdown;
use App\Models\ProjectWorkflowTask;
use App\Models\ProjectDocumentRevision;
use App\Models\CogmSubmission;
use App\Models\User;
use App\Notifications\CostingGroupChanged;
use App\Services\TrackingDocument\TrackingDocumentFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\BusinessCategoryContext;

class ProjectGroupController extends Controller
{
    public function costingInbox(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'active'));
        $allowedStatuses = ['active', 'history', 'all'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'active';
        }

        $query = CostingData::query()
            ->with([
                'customer', 'product', 'materialBreakdowns',
                'trackingRevision.project.product',
                'trackingRevision.latestCostingRevision',
                'trackingRevision.latestApproval.submitter',
                'trackingRevision.latestApproval.approver',
            ])
            ->whereNotNull('tracking_revision_id')
            ->orderByDesc('updated_at');
        BusinessCategoryContext::apply($query, 'trackingRevision.project');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('assy_no', 'like', "%{$search}%")
                    ->orWhere('assy_name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trackingRevision.project', function ($project) use ($search) {
                        $project->where('customer', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('part_number', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    });
            });
        }

        $statusMap = [
            'draft' => [DocumentRevision::STATUS_PENDING_FORM_INPUT, DocumentRevision::STATUS_SUDAH_COSTING, DocumentRevision::STATUS_COGM_GENERATED],
            'pricing' => [DocumentRevision::STATUS_PENDING_PRICING],
            'rejected' => [DocumentRevision::STATUS_REJECTED_BY_COORDINATOR],
            'waiting' => [DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL],
            'approved' => [DocumentRevision::STATUS_APPROVED_BY_COORDINATOR],
            'sent' => [DocumentRevision::STATUS_SUBMITTED_TO_MARKETING],
            'active' => [
                DocumentRevision::STATUS_PENDING_FORM_INPUT,
                DocumentRevision::STATUS_SUDAH_COSTING,
                DocumentRevision::STATUS_PENDING_PRICING,
                DocumentRevision::STATUS_COGM_GENERATED,
                DocumentRevision::STATUS_REJECTED_BY_COORDINATOR,
                DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
                DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            ],
            'history' => [DocumentRevision::STATUS_SUBMITTED_TO_MARKETING],
        ];
        if (isset($statusMap[$status])) {
            $query->whereHas('trackingRevision', fn ($revision) => $revision->whereIn('status', $statusMap[$status]));
        }

        $pendingCostingTasks = collect();
        if ($status !== 'history') {
            $pendingQuery = ProjectWorkflowTask::query()
                ->with(['revision.latestCostingRevision', 'project.product'])
                ->where('stage', ProjectWorkflowTask::STAGE_COSTING)
                ->whereIn('status', [ProjectWorkflowTask::STATUS_PENDING, ProjectWorkflowTask::STATUS_IN_PROGRESS])
                ->whereHas('revision')
                ->whereDoesntHave('revision.costingData')
                ->orderByDesc('updated_at');
            BusinessCategoryContext::apply($pendingQuery);

            if ($search !== '') {
                $pendingQuery->whereHas('project', function ($project) use ($search) {
                    $project->where('customer', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('part_number', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%");
                });
            }

            $pendingCostingTasks = $pendingQuery->get();
        }

        $items = $query->paginate(20)->withQueryString();
        $userRole = (string) optional($request->user())->role;
        $items->getCollection()->transform(function (CostingData $costing) use ($userRole) {
            $revision = $costing->trackingRevision;
            $approval = $revision?->latestApproval;
            $openUnpriced = $revision?->unpricedParts()->whereNull('resolved_at')->count() ?? 0;
            $revisionStatus = (string) ($revision?->status ?? '');

            $costing->workflow_label = match ($revisionStatus) {
                DocumentRevision::STATUS_PENDING_PRICING => 'Menunggu harga material',
                DocumentRevision::STATUS_REJECTED_BY_COORDINATOR => 'Perlu revisi costing',
                DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL => 'Menunggu approval coordinator',
                DocumentRevision::STATUS_APPROVED_BY_COORDINATOR => 'Approved, siap dikirim',
                DocumentRevision::STATUS_SUBMITTED_TO_MARKETING => 'Sudah dikirim ke Marketing',
                default => $openUnpriced > 0 ? 'Ada part belum memiliki harga' : 'Costing sedang dikerjakan',
            };
            $costing->workflow_class = match ($revisionStatus) {
                DocumentRevision::STATUS_REJECTED_BY_COORDINATOR => 'danger',
                DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL, DocumentRevision::STATUS_PENDING_PRICING => 'warning',
                DocumentRevision::STATUS_APPROVED_BY_COORDINATOR, DocumentRevision::STATUS_SUBMITTED_TO_MARKETING => 'success',
                default => 'info',
            };
            $costing->open_unpriced_count = $openUnpriced;
            $costing->can_submit_approval = in_array($userRole, ['admin', 'admin_costing', 'editor'], true)
                && $openUnpriced === 0
                && in_array($revisionStatus, [DocumentRevision::STATUS_SUDAH_COSTING, DocumentRevision::STATUS_COGM_GENERATED, DocumentRevision::STATUS_REJECTED_BY_COORDINATOR], true);
            $costing->can_approve = in_array($userRole, ['admin', 'coordinator_costing'], true)
                && $revisionStatus === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL;
            $costing->can_send = in_array($userRole, ['admin', 'coordinator_costing'], true)
                && $revisionStatus === DocumentRevision::STATUS_APPROVED_BY_COORDINATOR;
            $costing->cogm_value = (float) $costing->material_cost + (float) $costing->labor_cost
                + (float) $costing->overhead_cost + (float) $costing->scrap_cost;
            $costing->approval = $approval;

            return $costing;
        });

        return view('costing.inbox', compact('items', 'pendingCostingTasks', 'search', 'status'));
    }

    public function uploadCostingRevision(Request $request, DocumentRevision $revision)
    {
        $data = $request->validate([
            'revision_type' => ['required', 'in:price,partlist,umh'],
            'revision_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'revision_type.required' => 'Pilih jenis update dokumen.',
            'revision_file.required' => 'Pilih file Excel yang akan diunggah.',
            'revision_file.mimes' => 'Dokumen update harus berupa file Excel (.xls atau .xlsx).',
        ]);

        $revision->loadMissing('project');
        $file = $data['revision_file'];
        $path = $file->store('workflow/costing-revisions/'.$revision->id.'/'.$data['revision_type']);
        $costingTask = $revision->workflowTasks()->where('stage', ProjectWorkflowTask::STAGE_COSTING)->latest('id')->first();

        $submission = DB::transaction(function () use ($request, $revision, $costingTask, $data, $file, $path) {
            ProjectDocumentRevision::create([
                'document_project_id' => $revision->document_project_id,
                'document_revision_id' => $revision->id,
                'workflow_task_id' => $costingTask?->id,
                'revision_type' => $data['revision_type'],
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'description' => $data['description'] ?? null,
                'uploaded_by' => $request->user()->id,
            ]);

            if ($data['revision_type'] === 'partlist') {
                $revision->update([
                    'partlist_original_name' => $file->getClientOriginalName(),
                    'partlist_file_path' => $path,
                    'partlist_update_count' => ((int) $revision->partlist_update_count) + 1,
                    'partlist_updated_at' => now(),
                ]);
            } elseif ($data['revision_type'] === 'umh') {
                $revision->update([
                    'umh_original_name' => $file->getClientOriginalName(),
                    'umh_file_path' => $path,
                    'umh_update_count' => ((int) $revision->umh_update_count) + 1,
                    'umh_updated_at' => now(),
                ]);
            }

            $submission = CogmSubmission::where('document_revision_id', $revision->id)->latest('submitted_at')->first();
            if ($submission) {
                $submission->update([
                    'update_count' => ((int) $submission->update_count) + 1,
                    'last_updated_by' => $request->user()->name,
                    'last_updated_at' => now(),
                ]);
                $submission->events()->create([
                    'user_id'=>$request->user()->id,'event_type'=>$data['revision_type'].'_updated','source'=>'costing',
                    'title'=>$data['revision_type']==='price'?'Harga diperbarui dari Costing':'Dokumen Costing diperbarui',
                    'description'=>$data['description'] ?? ('File '.$file->getClientOriginalName().' diunggah.'),
                    'cogm_value'=>$submission->cogm_value,
                ]);
            }

            return $submission;
        });

        $label = match ($data['revision_type']) {
            'price' => 'Update harga', 'partlist' => 'Update Partlist', 'umh' => 'Update UMH',
        };

        if ($submission) {
            $picName = mb_strtolower(trim((string) $submission->pic_marketing));
            $recipients = User::query()->where('role', 'marketing')
                ->when($picName !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [$picName]))
                ->get();
            $payload = [
                'event' => $data['revision_type'].'_updated',
                'title' => 'Revisi Form Costing',
                'message' => $label.' untuk '.$revision->project->part_number.' telah dikirim ke Inbox Marketing.',
                'a00_number' => $revision->project->part_number,
                'url' => route('marketing.cogm-inbox', absolute: false),
            ];
            $recipients->each->notify(new CostingGroupChanged($payload));
        }

        $message = $label.' berhasil diunggah dan disimpan pada riwayat project.';
        if ($submission) $message .= ' Inbox Marketing dan notifikasi PIC Marketing telah diperbarui.';
        return back()->with('success', $message);
    }

    public function destroyGroup(Request $request, TrackingDocumentFileService $fileService)
    {
        $validated = $request->validate([
            'project_ids' => ['required','array','min:1'],
            'project_ids.*' => ['required','integer','distinct','exists:document_projects,id'],
        ]);
        $projects = DocumentProject::whereIn('id', $validated['project_ids'])->get();

        DB::transaction(function () use ($projects, $fileService) {
            foreach ($projects as $project) {
                $fileService->deletePaths($fileService->collectProjectFilePaths($project));
                $project->delete();
            }
        });

        return redirect()->route('project')->with('success', 'Project berhasil dihapus.');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $revisionsQuery = DocumentRevision::with([
            'project.product',
            'project.a00Form.creator',
            'project.a00Item.form',
            'latestApproval.submitter',
            'latestApproval.approver',
            'latestApproval.rejecter',
            'latestSubmission.comments.user',
            'latestSubmission.events.user',
            'workflowTasks.completedBy',
            'workflowTasks.assignedUser',
        ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        BusinessCategoryContext::apply($revisionsQuery);
        $revisions = $revisionsQuery->get();

        $costingByRevisionId = CostingData::with(['customer', 'product', 'materialBreakdowns'])
            ->whereNotNull('tracking_revision_id')
            ->get()
            ->keyBy('tracking_revision_id');

        $drawingByPartNumber = DocumentControlRegistration::query()
            ->whereNotNull('part_number')
            ->orderByDesc('registration_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($drawing) => $this->normalizePartNumber($drawing->part_number))
            ->map->first();

        $userRole = (string) optional($request->user())->role;

        $children = $revisions->map(function (DocumentRevision $revision) use ($costingByRevisionId, $drawingByPartNumber, $userRole) {
            $project = $revision->project;
            $costing = $costingByRevisionId->get($revision->id);
            $latestApproval = $revision->latestApproval;
            $latestSubmission = $revision->latestSubmission;
            $cogmValue = $costing ? $this->cogmValue($costing) : null;

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
            $drawing = $drawingByPartNumber->get($this->normalizePartNumber($partNumber));
            $progress = $this->buildProgress($revision, $costing, $drawing, $latestApproval, $latestSubmission);
            $a00Form = $project->a00Item?->form ?? $project->a00Form;

            return (object) [
                'revision' => $revision,
                'project' => $project,
                'costing' => $costing,
                'latest_approval' => $latestApproval,
                'latest_submission' => $latestSubmission,
                'cogm_value' => $cogmValue,
                'a00_form_id' => $a00Form?->id,
                'a00_document_number' => $this->cleanText($a00Form?->document_number ?? ''),

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
                'progress' => $progress,
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

        $sharedA00Counts = $children
            ->filter(fn ($item) => !empty($item->a00_form_id))
            ->groupBy('a00_form_id')
            ->map(fn (Collection $items) => $items->pluck('project.id')->filter()->unique()->count());
        $children->each(function ($item) use ($sharedA00Counts) {
            $item->shared_a00_count = $item->a00_form_id
                ? (int) ($sharedA00Counts[$item->a00_form_id] ?? 0)
                : 0;
        });

        $groups = $children
            ->groupBy(fn ($item) => $item->a00_form_id
                ? 'a00:'.$item->a00_form_id
                : $this->groupKey($item->business_category, $item->customer, $item->model)
                    .'|assy:'.$this->normalizePartNumber($item->part_number))
            ->map(function (Collection $items) {
                $first = $items->first();
                $groupedByA00 = !empty($first->a00_form_id);

                return (object) [
                    'key' => $groupedByA00
                        ? 'a00:'.$first->a00_form_id
                        : $this->groupKey($first->business_category, $first->customer, $first->model)
                            .'|assy:'.$this->normalizePartNumber($first->part_number),
                    'grouped_by_a00' => $groupedByA00,
                    'business_category' => $first->business_category,
                    'customer' => $first->customer,
                    'model' => $this->joinUnique($items->pluck('model')),
                    'project_name' => $this->joinUnique($items->pluck('part_name')),
                    'assy_numbers' => $this->joinUnique($items->pluck('part_number')),
                    'pic_engineering' => $this->joinUnique($items->pluck('pic_engineering')),
                    'pic_marketing' => $this->joinUnique($items->pluck('pic_marketing')),
                    'created_at' => $items->sortBy('created_at')->first()->created_at,
                    'updated_at' => $items->sortByDesc('updated_at')->first()->updated_at,
                    'total_part_number' => $items->pluck('part_number')->filter()->unique()->count(),
                    'total_items' => $items->count(),
                    'shared_a00_labels' => $items
                        ->filter(fn ($item) => ($item->shared_a00_count ?? 0) > 1)
                        ->map(fn ($item) => (object) [
                            'key' => $item->a00_form_id,
                            'number' => $item->a00_document_number ?: 'A00 #' . $item->a00_form_id,
                            'project_count' => $item->shared_a00_count,
                        ])
                        ->unique('key')
                        ->values(),
                    'status_summary' => $items
                        ->groupBy('status_label')
                        ->map(fn (Collection $statusItems) => (object) [
                            'label' => $statusItems->first()->status_label,
                            'class' => $statusItems->first()->status_class,
                            'count' => $statusItems->count(),
                        ])
                        ->values(),
                    'progress' => collect(['a00','drawing','breakdown','costing','new-part-request','submit','cogm'])->map(function ($key) use ($items) {
                        $steps = $items->map(fn ($item) => collect($item->progress)->firstWhere('key', $key));
                        $completed = $steps->every(fn ($step) => ($step['state'] ?? null) === 'done');
                        $active = !$completed && $steps->contains(fn ($step) => ($step['state'] ?? null) === 'active');
                        $sample = $steps->first();
                        $activeStatuses = $steps->filter(fn ($step) => ($step['state'] ?? null) === 'active')
                            ->pluck('status')->filter()->unique()->implode(' / ');
                        return [
                            'key' => $key,
                            'label' => $sample['label'],
                            'state' => $completed ? 'done' : ($active ? 'active' : 'pending'),
                            'status' => $completed ? 'Selesai' : ($active ? ($activeStatuses ?: 'Sedang proses') : 'Belum dimulai'),
                            'date' => $steps->pluck('date')->filter()->sortDesc()->first(),
                            'time' => $steps->pluck('time')->filter()->sortDesc()->first(),
                            'pic' => $completed || $active ? $steps->pluck('pic')->filter(fn ($pic) => $pic && $pic !== '-')->unique()->implode(', ') : '-',
                        ];
                    })->values(),
                    'items' => $items
                        ->sortBy([
                            ['part_number', 'asc'],
                            ['revision_label', 'asc'],
                        ])
                        ->values(),
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

    private function buildProgress(DocumentRevision $revision, ?CostingData $costing, $drawing, $approval, $submission): array
    {
        $workflowTasks = $revision->workflowTasks->keyBy('stage');
        $drawingTask = $workflowTasks->get('drawing');
        $breakdownTask = $workflowTasks->get('breakdown');
        $costingTask = $workflowTasks->get('costing');
        $isManualBreakdown = data_get($breakdownTask?->metadata, 'source') === 'manual_breakdown';
        $hasA00 = !$isManualBreakdown && (in_array($revision->a00, ['ada','tidak ada'], true) || $revision->a00_received_date || $revision->status === DocumentRevision::STATUS_A00_ISSUED);
        $hasDrawing = $drawingTask
            ? $drawingTask->status === 'completed'
            : (bool) $drawing;
        $hasBreakdown = $breakdownTask
            ? $breakdownTask->status === 'completed'
            : ($costing && $costing->materialBreakdowns->isNotEmpty());
        $revisionStatus = (string) $revision->status;
        $isRejected = $revisionStatus === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR;
        $hasCosting = !$isRejected && in_array($revisionStatus, [
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ], true);
        $hasSubmit = in_array($revisionStatus, [
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ], true);
        $hasCogm = (bool) $submission?->submitted_at;
        $openUnpricedCount = $revision->unpricedParts()->whereNull('resolved_at')->count();
        $hasNewPartRequest = $openUnpricedCount > 0 || $revision->unpricedParts()->whereNotNull('resolved_at')->exists();
        $hasNewPartPricing = $hasNewPartRequest && $openUnpricedCount === 0;
        $newPartActor = $revision->unpricedParts()
            ->whereNotNull('resolved_at')
            ->with('resolvedBy')
            ->latest('resolved_at')
            ->first()?->resolvedBy?->name;
        $hasPartlist = filled($revision->partlist_file_path);
        $hasUmh = filled($revision->umh_file_path);
        $isNewProjectDraft = data_get($breakdownTask?->metadata,'source') === 'new_project_draft'
            && $breakdownTask?->status === ProjectWorkflowTask::STATUS_PENDING
            && !filled($revision->partlist_file_path)
            && !filled($revision->umh_file_path)
            && !$costing
            && !$submission
            && $revision->a00 !== 'ada'
            && !$revision->a00_received_date;
        $breakdownStatus = $hasBreakdown
            ? 'Selesai'
            : ($hasPartlist
                ? ($hasUmh ? 'Dokumen lengkap' : 'Menunggu UMH')
                : ($hasUmh ? 'Menunggu Partlist' : 'Menunggu dokumen Engineering'));

        $definitions = [
            ['key'=>'a00','label'=>'A00','done'=>$hasA00,'date'=>$revision->a00_received_date ?? $revision->created_at,'pic'=>$revision->project?->a00Form?->creator?->name],
            ['key'=>'drawing','label'=>'Drawing','done'=>$hasDrawing,'date'=>$hasDrawing ? ($drawingTask?->completed_at ?? $drawing?->registration_date ?? $drawing?->created_at) : null,'pic'=>$drawingTask?->completedBy?->name ?? $drawingTask?->assignedUser?->name],
            ['key'=>'breakdown','label'=>'Breakdown','done'=>$hasBreakdown,'active'=>(bool)$breakdownTask,'status'=>$breakdownStatus,'date'=>$breakdownTask?->completed_at ?? $breakdownTask?->started_at ?? $breakdownTask?->available_at ?? ($hasBreakdown ? $costing?->updated_at : null),'pic'=>$breakdownTask?->completedBy?->name ?? $breakdownTask?->assignedUser?->name],
            ['key'=>'costing','label'=>'Costing','done'=>$hasCosting,'active'=>(bool)$costing || (bool)$costingTask,'status'=>$isRejected ? 'Perlu revisi' : ((bool)$costing ? 'Sedang proses' : 'Siap dimulai'),'date'=>$approval?->submitted_at ?? $costingTask?->started_at ?? $costingTask?->available_at ?? $costing?->updated_at,'pic'=>$approval?->submitter?->name ?? $costingTask?->completedBy?->name ?? $costingTask?->assignedUser?->name],
            ['key'=>'new-part-request','label'=>'New Part Request','done'=>$hasNewPartPricing,'active'=>$openUnpricedCount > 0,'status'=>$openUnpricedCount > 0 ? $openUnpricedCount.' part menunggu harga baru' : ($hasNewPartRequest ? 'Harga baru sudah lengkap' : 'Tidak ada part baru'),'date'=>$hasNewPartRequest ? $revision->updated_at : null,'pic'=>$newPartActor],
            ['key'=>'submit','label'=>'Submit','done'=>$hasSubmit,'active'=>$revisionStatus === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,'status'=>'Menunggu approval coordinator','date'=>$approval?->approved_at ?? $approval?->submitted_at,'pic'=>$approval?->approver?->name ?? $approval?->submitter?->name ?? '-'],
            ['key'=>'cogm','label'=>'COGM','done'=>$hasCogm,'date'=>$submission?->submitted_at,'pic'=>$submission?->submitted_by ?? $submission?->pic_marketing ?? '-'],
        ];
        $lastDone = collect($definitions)->search(fn ($step) => !$step['done']);
        $activeIndex = $lastDone === false ? null : $lastDone;

        return collect($definitions)->map(function ($step, $index) use ($activeIndex, $isManualBreakdown, $isNewProjectDraft) {
            $state = $step['done'] ? 'done' : (($step['active'] ?? false) || $index === $activeIndex ? 'active' : 'pending');
            if ($isNewProjectDraft) $state = 'pending';
            if ($isManualBreakdown) {
                if (in_array($step['key'], ['a00','drawing'], true)) $state = 'pending';
                if ($step['key'] === 'breakdown' && !$step['done']) $state = 'active';
            }
            return [
                'key'=>$step['key'],'label'=>$step['label'],'state'=>$state,
                'status'=>$isManualBreakdown && in_array($step['key'],['a00','drawing'],true)
                    ? 'Dilewati — project dimulai dari Breakdown'
                    : ($state === 'done' ? 'Selesai' : ($state === 'active' ? ($step['status'] ?? 'Sedang proses') : 'Belum dimulai')),
                'date'=>$step['date'] ? \Carbon\Carbon::parse($step['date'])->locale('id')->translatedFormat('d F Y') : null,
                'time'=>$step['date'] ? \Carbon\Carbon::parse($step['date'])->format('H:i') : null,
                'pic'=>$step['pic'] ?: '-',
            ];
        })->all();
    }

    private function normalizePartNumber(?string $value): string
    {
        return mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $value));
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
