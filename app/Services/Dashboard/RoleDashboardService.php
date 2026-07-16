<?php

namespace App\Services\Dashboard;

use App\Models\DocumentRevision;
use App\Models\ExportJob;
use App\Models\SystemEvent;
use App\Models\UatFeedback;
use App\Models\User;

class RoleDashboardService
{
    public function build(User $user): array
    {
        $role = (string) $user->role;
        $base = ['role' => $role, 'title' => 'Workspace '.ucwords(str_replace('_', ' ', $role)), 'message' => 'Ringkasan tindakan yang paling relevan untuk role Anda.', 'cards' => []];
        $pendingApproval = DocumentRevision::where('status', DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL)->count();
        $pendingCosting = DocumentRevision::whereIn('status', [DocumentRevision::STATUS_PENDING_FORM_INPUT, DocumentRevision::STATUS_PENDING_PRICING])->count();
        $marketing = DocumentRevision::where('status', DocumentRevision::STATUS_APPROVED_BY_COORDINATOR)->count();
        $base['cards'] = match ($role) {
            'admin' => [['label' => 'Critical Events', 'value' => SystemEvent::where('severity', 'critical')->where('occurred_at', '>=', now()->subDays(7))->count(), 'url' => route('system-center.index', absolute: false)], ['label' => 'Open UAT', 'value' => UatFeedback::whereIn('status', ['open', 'in_progress'])->count(), 'url' => route('uat-feedback.index', absolute: false)], ['label' => 'Exports', 'value' => ExportJob::count(), 'url' => route('exports.index', absolute: false)]],
            'coordinator_costing' => [['label' => 'Waiting Approval', 'value' => $pendingApproval, 'url' => route('project', absolute: false)], ['label' => 'Ready for Marketing', 'value' => $marketing, 'url' => route('marketing.cogm-inbox', absolute: false)]],
            'marketing' => [['label' => 'Ready to Review', 'value' => $marketing, 'url' => route('marketing.cogm-inbox', absolute: false)], ['label' => 'Submitted COGM', 'value' => DocumentRevision::where('status', DocumentRevision::STATUS_SUBMITTED_TO_MARKETING)->count(), 'url' => route('marketing.cogm-inbox', absolute: false)]],
            default => [['label' => 'Pending Costing', 'value' => $pendingCosting, 'url' => route('my-tasks', absolute: false)], ['label' => 'Waiting Approval', 'value' => $pendingApproval, 'url' => route('project', absolute: false)]],
        };

        return $base;
    }
}
