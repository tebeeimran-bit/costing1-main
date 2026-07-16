<?php

namespace App\Services\Costing;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use Illuminate\Validation\ValidationException;

class CostingLockService
{
    public function assertEditable(CostingData $costing): void
    {
        if (! $costing->tracking_revision_id) {
            return;
        }
        $status = DocumentRevision::query()->whereKey($costing->tracking_revision_id)->value('status');
        if (in_array($status, [DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL, DocumentRevision::STATUS_APPROVED_BY_COORDINATOR, DocumentRevision::STATUS_SUBMITTED_TO_MARKETING], true)) {
            throw ValidationException::withMessages(['costing_data_id' => 'Costing terkunci karena sudah masuk approval. Buat revisi baru untuk melakukan perubahan.']);
        }
    }
}
