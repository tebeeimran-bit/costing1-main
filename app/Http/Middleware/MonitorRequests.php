<?php

namespace App\Http\Middleware;

use App\Models\SystemEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MonitorRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $startMemory = memory_get_usage(true);
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->record($request, 'error', 'critical', 500, $started, $startMemory, $e->getMessage(), ['exception' => get_class($e)]);
            throw $e;
        }
        $duration = (int) round((microtime(true) - $started) * 1000);
        $threshold = (int) config('operations.slow_request_ms', 750);
        if ($response->getStatusCode() >= 500 || $duration >= $threshold) {
            $this->record($request, $response->getStatusCode() >= 500 ? 'error' : 'performance', $response->getStatusCode() >= 500 ? 'critical' : 'warning', $response->getStatusCode(), $started, $startMemory, $duration >= $threshold ? 'Slow request detected' : null);
        }
        $response->headers->set('Server-Timing', 'app;dur='.$duration);

        return $response;
    }

    private function record(Request $request, string $type, string $severity, int $status, float $started, int $memory, ?string $message = null, array $context = []): void
    {
        try {
            SystemEvent::create(['type' => $type, 'severity' => $severity, 'route' => $request->route()?->getName() ?: $request->path(), 'method' => $request->method(), 'status_code' => $status, 'duration_ms' => (int) round((microtime(true) - $started) * 1000), 'memory_kb' => (int) max(0, (memory_get_peak_usage(true) - $memory) / 1024), 'user_id' => $request->user()?->id, 'message' => $message, 'context' => $context, 'occurred_at' => now()]);
        } catch (Throwable) {
        }
    }
}
