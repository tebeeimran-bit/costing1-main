<?php

namespace App\Http\Controllers;

use App\Models\CogmSubmission;
use App\Models\CostingApproval;
use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\UnpricedPart;
use App\Models\ProjectA00Item;
use App\Models\CostingGroupVersion;
use App\Services\Costing\CostingGroupService;
use Illuminate\Http\Request;
use App\Support\BusinessCategoryContext;
use Illuminate\Support\Facades\DB;

class CostingApprovalController extends Controller
{
    public function submit(Request $request, DocumentRevision $revision)
    {
        $this->authorizeRole($request, ['admin', 'admin_costing', 'editor']);

        $costing = $this->costingForRevision($revision);
        if (!$costing) {
            return back()->with('error', 'Submit approval ditolak karena form costing untuk project ini belum ada.');
        }

        if ($this->hasOpenUnpricedParts($revision)) {
            return back()->with('warning', 'Submit approval ditolak karena masih ada part tanpa harga. Selesaikan pricing dulu.');
        }

        if (in_array($revision->status, [
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ], true)) {
            return back()->with('warning', 'Project ini sudah masuk tahap approval atau sudah dikirim ke marketing.');
        }

        $validated = $request->validate([
            'submit_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $revision, $costing, $validated) {
            CostingApproval::create([
                'document_revision_id' => $revision->id,
                'costing_data_id' => $costing->id,
                'status' => CostingApproval::STATUS_WAITING,
                'cogm_value' => $this->cogmValue($costing),
                'submitted_by_id' => $request->user()->id,
                'submitted_at' => now(),
                'submit_notes' => $validated['submit_notes'] ?? null,
            ]);

            $revision->update([
                'status' => DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            ]);
        });
        $this->refreshCostingGroup($revision);

        return back()->with('success', 'Costing berhasil disubmit ke Coordinator Costing untuk approval.');
    }

    public function approve(Request $request, DocumentRevision $revision)
    {
        $this->authorizeRole($request, ['admin', 'coordinator_costing']);

        if ($revision->status !== DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL) {
            return back()->with('warning', 'Project hanya bisa di-approve saat status Waiting Approval Coordinator.');
        }

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $revision, $validated) {
            $approval = $this->approvalForRevision($revision);
            $approval->update([
                'status' => CostingApproval::STATUS_APPROVED,
                'approved_by_id' => $request->user()->id,
                'approved_at' => now(),
                'approval_notes' => $validated['approval_notes'] ?? null,
            ]);

            $revision->update([
                'status' => DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            ]);
        });
        $this->refreshCostingGroup($revision);

        return back()->with('success', 'COGM berhasil di-approve oleh Coordinator Costing.');
    }

    public function reject(Request $request, DocumentRevision $revision)
    {
        $this->authorizeRole($request, ['admin', 'coordinator_costing']);

        if ($revision->status !== DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL) {
            return back()->with('warning', 'Project hanya bisa di-reject saat status Waiting Approval Coordinator.');
        }

        $validated = $request->validate([
            'rejection_notes' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $revision, $validated) {
            $approval = $this->approvalForRevision($revision);
            $approval->update([
                'status' => CostingApproval::STATUS_REJECTED,
                'rejected_by_id' => $request->user()->id,
                'rejected_at' => now(),
                'rejection_notes' => $validated['rejection_notes'],
            ]);

            $revision->update([
                'status' => DocumentRevision::STATUS_REJECTED_BY_COORDINATOR,
            ]);
        });
        $this->refreshCostingGroup($revision);

        return back()->with('success', 'Costing dikembalikan ke Admin Costing dengan catatan revisi.');
    }

    public function sendToMarketing(Request $request, DocumentRevision $revision)
    {
        $this->authorizeRole($request, ['admin', 'coordinator_costing']);

        if ($revision->status !== DocumentRevision::STATUS_APPROVED_BY_COORDINATOR) {
            return back()->with('warning', 'COGM hanya bisa dikirim setelah approved by Coordinator.');
        }

        $costing = $this->costingForRevision($revision);
        if (!$costing) {
            return back()->with('error', 'COGM tidak bisa dikirim karena data costing tidak ditemukan.');
        }

        $validated = $request->validate([
            'pic_marketing' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $picMarketing = trim((string) ($validated['pic_marketing'] ?? $revision->pic_marketing ?? ''));
        if ($picMarketing === '' || $picMarketing === '-') {
            $picMarketing = 'Team Marketing';
        }

        DB::transaction(function () use ($request, $revision, $costing, $validated, $picMarketing) {
            CogmSubmission::create([
                'document_revision_id' => $revision->id,
                'submitted_at' => now(),
                'pic_marketing' => $picMarketing,
                'cogm_value' => $this->cogmValue($costing),
                'submitted_by' => $request->user()->name,
                'notes' => $validated['notes'] ?? null,
            ]);

            $approval = $this->approvalForRevision($revision);
            $approval->update([
                'status' => CostingApproval::STATUS_SUBMITTED_TO_MARKETING,
            ]);

            $revision->update([
                'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
                'pic_marketing' => $picMarketing,
            ]);
        });
        $this->refreshCostingGroup($revision);

        return back()->with('success', 'COGM approved berhasil dikirim ke Team Marketing.');
    }

    public function marketingInbox(Request $request)
    {
        $this->authorizeRole($request, ['admin', 'admin_costing', 'marketing', 'coordinator_costing']);
        $search = trim((string) $request->query('search'));

        $submissions = CogmSubmission::with(['revision.project.product','revision.latestCostingRevision'])
            ->when(
                (string) $request->user()->role === 'marketing',
                fn ($query) => $query->whereRaw('LOWER(TRIM(pic_marketing)) = ?', [
                    mb_strtolower(trim((string) $request->user()->name)),
                ])
            )
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('pic_marketing', 'like', "%{$search}%")
                    ->orWhere('submitted_by', 'like', "%{$search}%")
                    ->orWhereHas('revision.project', fn ($projectQuery) => $projectQuery
                        ->where('customer', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('part_number', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%"));
            }))
            ->orderByRaw('COALESCE(last_updated_at, submitted_at) DESC');
        BusinessCategoryContext::apply($submissions, 'revision.project');
        $submissions=$submissions->paginate(15)->withQueryString();

        $name=mb_strtolower(trim((string)$request->user()->name));
        $groupSubmissions=CostingGroupVersion::with(['group.a00Form','group.activeItems.a00Item','group.activeItems.project'])
            ->where('type','final')->where('status','submitted')
            ->when((string)$request->user()->role==='marketing',fn($query)=>$query->whereHas('group',fn($groupQuery)=>$groupQuery
                ->whereRaw('LOWER(TRIM(pic_marketing)) = ?',[$name])
                ->orWhereHas('activeItems',fn($itemQuery)=>$itemQuery->whereRaw('LOWER(TRIM(pic_marketing)) = ?',[$name]))))
            ->when($search !== '', fn($query) => $query->whereHas('group', fn($groupQuery) => $groupQuery
                ->where('pic_marketing','like',"%{$search}%")
                ->orWhereHas('a00Form',fn($a00Query)=>$a00Query
                    ->where('document_number','like',"%{$search}%")
                    ->orWhere('customer','like',"%{$search}%")
                    ->orWhere('model','like',"%{$search}%")
                    ->orWhere('assy_number','like',"%{$search}%"))
                ->orWhereHas('activeItems',fn($itemQuery)=>$itemQuery
                    ->where('pic_marketing','like',"%{$search}%")
                    ->orWhereHas('project',fn($projectQuery)=>$projectQuery
                        ->where('model','like',"%{$search}%")
                        ->orWhere('part_number','like',"%{$search}%")
                        ->orWhere('part_name','like',"%{$search}%")))))
            ->latest('submitted_at');
        if(BusinessCategoryContext::selected()){
            $categoryCode=BusinessCategoryContext::selected()?->code;
            $groupSubmissions->whereHas('group.activeItems.project.product',fn($product)=>$product->where('code',$categoryCode));
        }
        $groupSubmissions=$groupSubmissions->get();

        return view('reports.marketing-cogm-inbox', compact('submissions','groupSubmissions','search'));
    }

    public function storeMarketingComment(Request $request, CogmSubmission $submission)
    {
        $this->authorizeRole($request, ['admin', 'marketing']);
        $this->authorizeMarketingSubmission($request, $submission);
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $submission->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => trim($validated['comment']),
        ]);

        return back()->with('success', 'Komentar berhasil dikirim ke Team Costing.');
    }

    private function authorizeRole(Request $request, array $allowedRoles): void
    {
        $role = (string) ($request->user()->role ?? '');
        if (!in_array($role, $allowedRoles, true)) {
            abort(403, 'Role Anda tidak memiliki akses untuk aksi approval ini.');
        }
    }

    private function refreshCostingGroup(DocumentRevision $revision): void
    {
        $a00Item = ProjectA00Item::with('form')->where('document_revision_id', $revision->id)->first();
        if ($a00Item?->form) app(CostingGroupService::class)->syncFromA00($a00Item->form, auth()->id());
    }

    private function authorizeMarketingSubmission(Request $request, CogmSubmission $submission): void
    {
        if ((string) $request->user()->role !== 'marketing') {
            return;
        }

        abort_unless(
            mb_strtolower(trim((string) $submission->pic_marketing))
                === mb_strtolower(trim((string) $request->user()->name)),
            403,
            'COGM ini ditujukan untuk PIC Marketing lain.'
        );
    }

    private function costingForRevision(DocumentRevision $revision): ?CostingData
    {
        return CostingData::with(['customer', 'product'])
            ->where('tracking_revision_id', $revision->id)
            ->latest('id')
            ->first();
    }

    private function approvalForRevision(DocumentRevision $revision): CostingApproval
    {
        $approval = CostingApproval::where('document_revision_id', $revision->id)
            ->latest('id')
            ->first();

        if ($approval) {
            return $approval;
        }

        $costing = $this->costingForRevision($revision);

        return CostingApproval::create([
            'document_revision_id' => $revision->id,
            'costing_data_id' => $costing?->id,
            'status' => CostingApproval::STATUS_WAITING,
            'cogm_value' => $costing ? $this->cogmValue($costing) : null,
            'submitted_at' => now(),
        ]);
    }

    private function hasOpenUnpricedParts(DocumentRevision $revision): bool
    {
        return UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->exists();
    }

    private function cogmValue(CostingData $costing): float
    {
        return (float) ($costing->material_cost ?? 0)
            + (float) ($costing->labor_cost ?? 0)
            + (float) ($costing->overhead_cost ?? 0)
            + (float) ($costing->scrap_cost ?? 0);
    }
}
