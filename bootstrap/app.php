<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectCanonicalHost;
use App\Http\Middleware\RemovePoweredByHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::middleware([
                RedirectCanonicalHost::class,
                RemovePoweredByHeader::class,
            ])->group(base_path('routes/seo.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            RedirectCanonicalHost::class,
            RemovePoweredByHeader::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'midtrans/webhook',
            'doku/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
