<?php

namespace App\Http\Controllers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Models\ProjectComment;
use App\Models\ProjectTaskSetting;
use App\Models\User;
use App\Services\Project\ProjectActivityService;
use App\Services\Project\ProjectCompletenessService;
use App\Services\Project\ProjectDeadlineService;
use App\Services\Project\ProjectWorkflowService;
use App\Services\Project\RevisionComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProjectCollaborationController extends Controller
{
    public function show(Request $request, DocumentRevision $revision, ProjectWorkflowService $workflows, ProjectDeadlineService $deadlines, ProjectCompletenessService $completenessService, RevisionComparisonService $comparisonService)
    {
        $revision->load(['project.product', 'unpricedParts:id,document_revision_id,resolved_at', 'taskSetting.setBy', 'activities.user', 'comments.user']);
        $costing = CostingData::with(['customer', 'materialBreakdowns'])->withCount('materialBreakdowns')->where('tracking_revision_id', $revision->id)->latest('id')->first();
        $workflow = $workflows->build($revision, $costing, (string) $request->user()->role);
        $deadline = $deadlines->resolve($revision, $workflow);
        $completeness = $completenessService->build($revision, $costing);
        $previousRevision = DocumentRevision::query()
            ->where('document_project_id', $revision->document_project_id)
            ->where('version_number', '<', $revision->version_number)
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->first();
        $previousCosting = $previousRevision
            ? CostingData::with(['trackingRevision', 'materialBreakdowns'])
                ->where('tracking_revision_id', $previousRevision->id)
                ->latest('id')
                ->first()
            : null;
        $revisionComparison = $costing ? $comparisonService->build($costing, $previousCosting) : ['available' => false, 'components' => [], 'material_changes' => 0];
        $mentionUsers = User::query()->orderBy('name')->get(['id', 'name', 'email', 'role'])->map(fn (User $user) => (object) [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'handle' => $this->handle($user),
        ]);
        $canManageDeadline = in_array($request->user()->role, ['admin', 'admin_costing', 'coordinator_costing', 'editor'], true);

        return view('projects.collaboration', compact('revision', 'costing', 'previousCosting', 'revisionComparison', 'workflow', 'deadline', 'completeness', 'mentionUsers', 'canManageDeadline'));
    }

    public function updateDeadline(Request $request, DocumentRevision $revision, ProjectActivityService $activities)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'admin_costing', 'coordinator_costing', 'editor'], true), 403);
        $validated = $request->validate(['due_at' => ['nullable', 'date']]);
        $dueAt = filled($validated['due_at'] ?? null) ? Carbon::parse($validated['due_at'])->endOfDay() : null;

        ProjectTaskSetting::updateOrCreate(
            ['document_revision_id' => $revision->id],
            ['due_at' => $dueAt, 'set_by_id' => $request->user()->id]
        );
        $activities->record($revision->id, 'deadline_updated', $dueAt ? 'Deadline updated' : 'Custom deadline removed', $dueAt ? 'Due date set to '.$dueAt->format('d M Y').'.' : 'The workflow now uses its default SLA.');

        return back()->with('success', $dueAt ? 'Project deadline updated.' : 'Project deadline reset to the default SLA.');
    }

    public function storeComment(Request $request, DocumentRevision $revision, ProjectActivityService $activities)
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $handles = collect(Str::of($validated['body'])->matchAll('/@([a-zA-Z0-9._-]+)/')->all())->map(fn ($value) => Str::lower($value))->unique();
        $mentionedIds = User::query()->get(['id', 'name', 'email'])->filter(fn (User $user) => $handles->contains($this->handle($user)))->pluck('id')->values()->all();

        ProjectComment::create([
            'document_revision_id' => $revision->id,
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
            'mentioned_user_ids' => $mentionedIds ?: null,
        ]);
        $activities->record($revision->id, 'comment_added', 'Comment added', count($mentionedIds) ? count($mentionedIds).' team member(s) mentioned.' : null);

        return back()->with('success', 'Comment posted.');
    }

    public function destroyComment(Request $request, DocumentRevision $revision, ProjectComment $comment, ProjectActivityService $activities)
    {
        abort_unless($comment->document_revision_id === $revision->id, 404);
        abort_unless($request->user()->role === 'admin' || $comment->user_id === $request->user()->id, 403);
        $comment->delete();
        $activities->record($revision->id, 'comment_deleted', 'Comment removed');

        return back()->with('success', 'Comment removed.');
    }

    private function handle(User $user): string
    {
        $emailHandle = Str::before((string) $user->email, '@');

        return Str::lower($emailHandle !== '' ? $emailHandle : Str::slug($user->name, '_'));
    }
}
