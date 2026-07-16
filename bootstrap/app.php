<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\EnforceIdleSession;
use App\Http\Middleware\MonitorRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies for Codespaces environment
        $middleware->trustProxies(at: '*');

        // Gzip compress all responses
        $middleware->append(CompressResponse::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(MonitorRequests::class);
        $middleware->web(append: [EnforceIdleSession::class]);

        // Alias RBAC permission middleware
        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
