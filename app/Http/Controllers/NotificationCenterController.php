<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationState;
use App\Services\Notification\ProjectNotificationService;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    public function index(Request $request, ProjectNotificationService $service)
    {
        $items = $service->forUser($request->user());
        $enabledTypes = NotificationPreference::query()->where('user_id', $request->user()->id)->value('enabled_types') ?? NotificationPreference::TYPES;
        if (is_string($enabledTypes)) $enabledTypes = json_decode($enabledTypes, true) ?: NotificationPreference::TYPES;
        return view('notifications.index', compact('items', 'enabledTypes'));
    }

    public function markRead(Request $request)
    {
        $validated = $request->validate(['key' => ['required', 'string', 'max:160']]);
        NotificationState::updateOrCreate(
            ['user_id' => $request->user()->id, 'notification_key' => $validated['key']],
            ['read_at' => now(), 'dismissed_at' => null]
        );
        return $request->expectsJson() ? response()->json(['success' => true]) : back();
    }

    public function markAllRead(Request $request, ProjectNotificationService $service)
    {
        $now = now();
        $rows = $service->forUser($request->user())->map(fn ($item) => [
            'user_id' => $request->user()->id, 'notification_key' => $item['key'], 'read_at' => $now,
            'dismissed_at' => null, 'created_at' => $now, 'updated_at' => $now,
        ])->all();
        if ($rows) NotificationState::upsert($rows, ['user_id', 'notification_key'], ['read_at', 'dismissed_at', 'updated_at']);
        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    public function dismiss(Request $request)
    {
        $validated = $request->validate(['key' => ['required', 'string', 'max:160']]);
        NotificationState::updateOrCreate(
            ['user_id' => $request->user()->id, 'notification_key' => $validated['key']],
            ['read_at' => now(), 'dismissed_at' => now()]
        );
        return back()->with('success', 'Notifikasi disembunyikan.');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'enabled_types' => ['nullable', 'array'],
            'enabled_types.*' => ['string', 'distinct', 'in:' . implode(',', NotificationPreference::TYPES)],
        ]);
        NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['enabled_types' => array_values($validated['enabled_types'] ?? [])]
        );
        return back()->with('success', 'Preferensi notifikasi berhasil disimpan.');
    }
}
