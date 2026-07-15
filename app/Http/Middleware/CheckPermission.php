<?php

namespace App\Http\Middleware;

use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin melewati semua pengecekan permission
        if ($user->role === 'admin') {
            return $next($request);
        }

        // GET/HEAD = cukup 'view', method lain (POST/PUT/PATCH/DELETE) = butuh 'full'
        $minLevel = in_array($request->method(), ['GET', 'HEAD']) ? 'view' : 'full';

        if (!RolePermission::hasAccess($user->role, $module, $minLevel)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
