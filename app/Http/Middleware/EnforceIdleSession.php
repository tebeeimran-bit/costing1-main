<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $last = (int) $request->session()->get('last_activity_at', time());
            if (time() - $last > ((int) config('session.lifetime', 120) * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('warning', 'Sesi berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
            }$request->session()->put('last_activity_at', time());
        }

        return $next($request);
    }
}
