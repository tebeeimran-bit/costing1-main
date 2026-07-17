<?php

namespace App\Observers;

use App\Models\DocumentRevision;
use App\Services\Project\ProjectActivityService;

class DocumentRevisionObserver
{
    public function __construct(private ProjectActivityService $activities) {}

    public function created(DocumentRevision $revision): void
    {
        $this->activities->record($revision->id, 'revision_created', 'Project revision created', 'A new project revision was added to the workflow.');
    }

    public function updated(DocumentRevision $revision): void
    {
        if ($revision->wasChanged('status')) {
            $old = $this->statusLabel((string) $revision->getOriginal('status'));
            $new = $revision->status_label;
            $this->activities->record($revision->id, 'status_changed', 'Workflow status changed', $old . ' → ' . $new, ['from' => $old, 'to' => $new]);
            return;
        }

        $fields = collect(array_keys($revision->getChanges()))->reject(fn ($field) => $field === 'updated_at')->values();
        if ($fields->isNotEmpty()) {
            $this->activities->record($revision->id, 'revision_updated', 'Project revision updated', 'Updated: ' . $fields->map(fn ($field) => str_replace('_', ' ', $field))->implode(', '));
        }
    }

    private function statusLabel(string $status): string
    {
        $revision = new DocumentRevision(['status' => $status]);
        return $revision->status_label;
    }
}
