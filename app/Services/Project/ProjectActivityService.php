<?php

namespace App\Services\Project;

use App\Models\ProjectActivity;

class ProjectActivityService
{
    public function record(int $revisionId, string $type, string $title, ?string $description = null, array $metadata = [], ?int $userId = null): ProjectActivity
    {
        return ProjectActivity::create([
            'document_revision_id' => $revisionId,
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
