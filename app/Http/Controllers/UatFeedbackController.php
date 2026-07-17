<?php

namespace App\Http\Controllers;

use App\Models\UatFeedback;
use Illuminate\Http\Request;

class UatFeedbackController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $status = $request->query('status', 'all');
        $feedback = UatFeedback::with(['user', 'resolvedBy'])->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString();
        $counts = UatFeedback::selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');

        return view('uat.index', compact('feedback', 'counts', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('uatFeedback', [
            'category' => ['required', 'in:bug,usability,data,performance,suggestion'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:2000'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:1000'],
            'screenshot' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);
        $validated['page_url'] = $this->safePageUrl($request, $validated['page_url'] ?? null);
        $path = $request->file('screenshot')?->store('uat-feedback', 'public');
        unset($validated['screenshot']);
        UatFeedback::create($validated + ['user_id' => $request->user()->id, 'screenshot_path' => $path, 'status' => 'open']);

        return back()->with('uat_success', 'Laporan berhasil dikirim. Terima kasih atas masukannya.');
    }

    public function update(Request $request, UatFeedback $feedback)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $validated = $request->validate(['status' => ['required', 'in:open,in_progress,resolved,rejected'], 'resolution_notes' => ['nullable', 'string', 'max:3000']]);
        $resolved = in_array($validated['status'], ['resolved', 'rejected'], true);
        $feedback->update($validated + ['resolved_by_id' => $resolved ? $request->user()->id : null, 'resolved_at' => $resolved ? now() : null]);

        return back()->with('success', 'Status feedback berhasil diperbarui.');
    }

    private function safePageUrl(Request $request, ?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return null;
        }

        if (strcasecmp((string) ($parts['host'] ?? ''), $request->getHost()) !== 0) {
            return null;
        }

        $urlPort = (int) ($parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80));
        if ($urlPort !== $request->getPort()) {
            return null;
        }

        return $url;
    }
}
