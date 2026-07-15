<?php

namespace App\Services\TrackingDocument;

use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\Pic;
use App\Models\Plant;
use App\Models\Product;

class TrackingDocumentSharedDataService
{
    public function getFormOptions(): array
    {
        return [
            'products' => Product::orderBy('code')->get(),
            'businessCategories' => BusinessCategory::orderBy('code')->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'lines' => Product::query()->whereNotNull('line')->distinct('line')->orderBy('line')->pluck('line'),
            'plants' => Plant::orderBy('code')->orderBy('name')->get(),
            'periods' => CostingData::distinct('period')->orderBy('period', 'desc')->pluck('period'),
            'picsEngineering' => Pic::where('type', 'engineering')->orderBy('name')->get(),
            'picsMarketing' => Pic::where('type', 'marketing')->orderBy('name')->get(),
        ];
    }

    public function getIndexData(): array
    {
        $options = $this->getFormOptions();

        $projects = DocumentProject::with([
            'product',
            'revisions' => function ($query) {
                $query->with(['cogmSubmissions', 'latestSubmission'])
                    ->orderBy('version_number', 'desc')
                    ->orderBy('id', 'desc');
            },
        ])->get()
            ->filter(fn ($project) => $project->revisions->isNotEmpty())
            ->sortBy(function ($project) {
                $latestRevisionDate = optional($project->revisions->first()?->received_date);

                return $latestRevisionDate?->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        $revisions = $projects->flatMap(fn ($project) => $project->revisions)
            ->sortByDesc('id')
            ->values();

        return array_merge($options, [
            'projects' => $projects,
            'revisions' => $revisions,
        ]);
    }
}
