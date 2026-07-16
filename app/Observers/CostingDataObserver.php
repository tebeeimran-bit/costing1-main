<?php

namespace App\Observers;

use App\Models\CostingData;
use App\Services\Project\ProjectActivityService;

class CostingDataObserver
{
    public function __construct(private ProjectActivityService $activities) {}

    public function created(CostingData $costing): void
    {
        if ($costing->tracking_revision_id) {
            $this->activities->record((int) $costing->tracking_revision_id, 'costing_created', 'Costing data created', 'The Costing Form was saved for the first time.');
        }
    }

    public function updated(CostingData $costing): void
    {
        if (!$costing->tracking_revision_id) return;
        $fields = collect(array_keys($costing->getChanges()))->reject(fn ($field) => $field === 'updated_at')->values();
        if ($fields->isNotEmpty()) {
            $this->activities->record((int) $costing->tracking_revision_id, 'costing_updated', 'Costing data updated', $fields->count() . ' costing field(s) changed.');
        }
    }
}
