<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ApprovalDelegation;
use App\Models\LoginActivity;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Http\Request;

class SystemCenterController extends Controller
{
    public function index()
    {
        $events = SystemEvent::latest('occurred_at')->limit(100)->get();
        $logins = LoginActivity::latest('occurred_at')->limit(50)->get();
        $announcements = Announcement::latest()->get();
        $delegations = ApprovalDelegation::with(['delegator', 'delegate'])->latest()->get();
        $users = User::orderBy('name')->get();
        $delegators = User::whereIn('role', ['admin', 'coordinator_costing'])->orderBy('name')->get();
        $kpis = ['critical' => $events->where('severity', 'critical')->count(), 'slow' => $events->where('type', 'performance')->count(), 'failed_logins' => $logins->where('successful', false)->count(), 'avg_ms' => (int) round($events->whereNotNull('duration_ms')->avg('duration_ms') ?: 0)];

        return view('system-center.index', compact('events', 'logins', 'announcements', 'delegations', 'users', 'delegators', 'kpis'));
    }

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'body' => ['required', 'string', 'max:2000'], 'level' => ['required', 'in:info,success,warning,critical'], 'audiences' => ['nullable', 'array'], 'audiences.*' => ['in:admin,admin_costing,coordinator_costing,marketing,editor,viewer'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at']]);
        Announcement::create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Announcement dipublikasikan.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Announcement dihapus.');
    }

    public function storeDelegation(Request $request)
    {
        $data = $request->validate(['delegator_id' => ['required', 'integer', 'exists:users,id'], 'delegate_id' => ['required', 'integer', 'exists:users,id', 'different:delegator_id'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'reason' => ['nullable', 'string', 'max:255']]);
        abort_unless(User::whereKey($data['delegator_id'])->whereIn('role', ['admin', 'coordinator_costing'])->exists(), 422, 'Pemberi mandat harus Admin atau Coordinator Costing.');
        ApprovalDelegation::create($data);

        return back()->with('success', 'Delegasi approval aktif sesuai periode.');
    }

    public function destroyDelegation(ApprovalDelegation $delegation)
    {
        $delegation->update(['is_active' => false]);

        return back()->with('success', 'Delegasi dinonaktifkan.');
    }
}
