<?php

namespace App\Services\Project;

use App\Models\CostingData;
use App\Models\DocumentRevision;

class ProjectWorkflowService
{
    public function build(DocumentRevision $revision, ?CostingData $costing, string $userRole): array
    {
        $status = (string) $revision->status;
        $documentsComplete = filled($revision->partlist_file_path) && filled($revision->umh_file_path);
        $openUnpricedCount = $revision->relationLoaded('unpricedParts')
            ? $revision->unpricedParts->whereNull('resolved_at')->count()
            : $revision->unpricedParts()->whereNull('resolved_at')->count();

        $costingCompleteStatuses = [
            DocumentRevision::STATUS_SUDAH_COSTING,
            DocumentRevision::STATUS_COGM_GENERATED,
            DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ];
        $costingComplete = $costing !== null && in_array($status, $costingCompleteStatuses, true);
        $pricingComplete = $costing !== null && $openUnpricedCount === 0;
        $approvalComplete = in_array($status, [
            DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,
            DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
        ], true);
        $marketingComplete = $status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING;

        $steps = [
            $this->step('project', 'Project', true),
            $this->step('documents', 'Dokumen', $documentsComplete),
            $this->step('pricing', 'Harga Part', $pricingComplete),
            $this->step('costing', 'Costing', $costingComplete),
            $this->step('approval', 'Approval', $approvalComplete),
            $this->step('marketing', 'Marketing', $marketingComplete),
        ];

        $currentKey = $this->currentStepKey(
            $documentsComplete,
            $pricingComplete,
            $costingComplete,
            $approvalComplete,
            $marketingComplete
        );

        foreach ($steps as &$step) {
            if ($step['key'] !== $currentKey || $step['complete']) {
                continue;
            }

            $step['state'] = ($step['key'] === 'pricing' && $openUnpricedCount > 0)
                || ($step['key'] === 'costing' && $status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR)
                ? 'issue'
                : 'current';
        }
        unset($step);

        $completedCount = collect($steps)->where('complete', true)->count();

        return [
            'steps' => $steps,
            'completed_count' => $completedCount,
            'total_count' => count($steps),
            'progress' => (int) round(($completedCount / count($steps)) * 100),
            'open_unpriced_count' => $openUnpricedCount,
            'is_complete' => $marketingComplete,
            'next_action' => $this->nextAction(
                $revision,
                $costing,
                $userRole,
                $documentsComplete,
                $openUnpricedCount,
                $costingComplete,
                $status
            ),
        ];
    }

    private function step(string $key, string $label, bool $complete): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'complete' => $complete,
            'state' => $complete ? 'complete' : 'pending',
        ];
    }

    private function currentStepKey(
        bool $documentsComplete,
        bool $pricingComplete,
        bool $costingComplete,
        bool $approvalComplete,
        bool $marketingComplete
    ): ?string {
        return match (false) {
            $documentsComplete => 'documents',
            $pricingComplete => 'pricing',
            $costingComplete => 'costing',
            $approvalComplete => 'approval',
            $marketingComplete => 'marketing',
            default => null,
        };
    }

    private function nextAction(
        DocumentRevision $revision,
        ?CostingData $costing,
        string $userRole,
        bool $documentsComplete,
        int $openUnpricedCount,
        bool $costingComplete,
        string $status
    ): array {
        if ($status === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING) {
            return $this->action('Selesai', 'COGM sudah dikirim ke Marketing.', null, 'complete');
        }

        if (!$documentsComplete) {
            return $this->action(
                'Lengkapi Dokumen',
                'Partlist dan UMH wajib tersedia sebelum proses dilanjutkan.',
                route('database.project-documents', ['search' => $revision->project?->part_number], false),
                'link'
            );
        }

        if ($costing === null) {
            return $this->action(
                'Mulai Form Costing',
                'Costing untuk part ini belum dibuat.',
                route('form', ['tracking_revision_id' => $revision->id], false),
                'link'
            );
        }

        if ($openUnpricedCount > 0) {
            return $this->action(
                'Lengkapi ' . $openUnpricedCount . ' Harga Part',
                'Selesaikan seluruh unpriced parts sebelum submit approval.',
                route('unpriced-parts', absolute: false),
                'issue'
            );
        }

        if (!$costingComplete || $status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR) {
            return $this->action(
                $status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR ? 'Perbaiki Costing' : 'Lanjutkan Costing',
                $status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR
                    ? 'Costing ditolak Coordinator dan perlu diperbaiki.'
                    : 'Lengkapi komponen biaya dan generate COGM.',
                route('form', ['tracking_revision_id' => $revision->id], false),
                $status === DocumentRevision::STATUS_REJECTED_BY_COORDINATOR ? 'issue' : 'link'
            );
        }

        if (in_array($status, [DocumentRevision::STATUS_SUDAH_COSTING, DocumentRevision::STATUS_COGM_GENERATED], true)) {
            $canSubmit = in_array($userRole, ['admin', 'admin_costing', 'editor'], true);
            return $this->action(
                $canSubmit ? 'Submit Approval' : 'Menunggu Submit Approval',
                $canSubmit ? 'COGM siap dikirim ke Coordinator Costing.' : 'Admin Costing perlu melakukan submit approval.',
                $canSubmit ? '#workflow-actions-' . $revision->id : null,
                $canSubmit ? 'action' : 'waiting'
            );
        }

        if ($status === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL) {
            $canReview = in_array($userRole, ['admin', 'coordinator_costing'], true);
            return $this->action(
                $canReview ? 'Review Approval' : 'Menunggu Coordinator',
                $canReview ? 'Periksa COGM lalu approve atau reject.' : 'COGM sedang menunggu keputusan Coordinator Costing.',
                $canReview ? '#workflow-actions-' . $revision->id : null,
                $canReview ? 'action' : 'waiting'
            );
        }

        if ($status === DocumentRevision::STATUS_APPROVED_BY_COORDINATOR) {
            $canSend = in_array($userRole, ['admin', 'coordinator_costing'], true);
            return $this->action(
                $canSend ? 'Kirim ke Marketing' : 'Menunggu Pengiriman',
                $canSend ? 'COGM sudah approved dan siap dikirim.' : 'Coordinator perlu mengirim COGM ke Marketing.',
                $canSend ? '#workflow-actions-' . $revision->id : null,
                $canSend ? 'action' : 'waiting'
            );
        }

        return $this->action('Periksa Status', 'Buka detail project untuk menentukan tindakan berikutnya.', '#workflow-actions-' . $revision->id, 'action');
    }

    private function action(string $label, string $description, ?string $url, string $type): array
    {
        return compact('label', 'description', 'url', 'type');
    }
}
