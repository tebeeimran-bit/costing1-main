<?php

namespace App\Http\Controllers;

use App\Models\CostingDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostingDraftController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_key' => ['required', 'string', 'max:120'],
        ]);

        $draft = CostingDraft::query()
            ->where('user_id', $request->user()->id)
            ->where('draft_key', $validated['draft_key'])
            ->first();

        return response()->json([
            'draft' => $draft ? [
                'payload' => $draft->payload,
                'saved_at' => $draft->saved_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_key' => ['required', 'string', 'max:120'],
            'tracking_revision_id' => ['nullable', 'integer', 'exists:document_revisions,id'],
            'costing_data_id' => ['nullable', 'integer', 'exists:costing_data,id'],
            'payload' => ['required', 'array', 'max:15000'],
        ]);

        $payload = collect($validated['payload'])
            ->filter(fn ($item) => is_array($item) && isset($item['name']) && is_string($item['name']))
            ->take(15000)
            ->map(fn ($item) => [
                'name' => mb_substr((string) $item['name'], 0, 255),
                'value' => mb_substr((string) ($item['value'] ?? ''), 0, 20000),
                'type' => mb_substr((string) ($item['type'] ?? 'text'), 0, 30),
                'checked' => (bool) ($item['checked'] ?? false),
            ])
            ->values()
            ->all();

        $draft = CostingDraft::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'draft_key' => $validated['draft_key'],
            ],
            [
                'tracking_revision_id' => $validated['tracking_revision_id'] ?? null,
                'costing_data_id' => $validated['costing_data_id'] ?? null,
                'payload' => $payload,
                'saved_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'saved_at' => $draft->saved_at->toIso8601String(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_key' => ['required', 'string', 'max:120'],
        ]);

        CostingDraft::query()
            ->where('user_id', $request->user()->id)
            ->where('draft_key', $validated['draft_key'])
            ->delete();

        return response()->json(['success' => true]);
    }
}
