<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Identity\Infrastructure\Http\Middleware\EnsurePasswordChanged;
use App\Modules\Identity\Infrastructure\Http\Middleware\EnsureUserIsActive;
use App\Shared\Exceptions\DomainException;
use App\Shared\Exceptions\StaleContentVersionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureUserIsActive::class,
            EnsurePasswordChanged::class,
            ContentSecurityPolicy::class,
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (StaleContentVersionException $e, Request $request) {
            $message = $e->getMessage();

            if ($request->expectsJson() || $request->header('X-Inertia') !== null) {
                return response()->json(['message' => $message], 409);
            }

            return response($message, 409);
        });
        $exceptions->render(function (DomainException $e, Request $request) {
            $errors = [$e->field() => $e->getMessage()];

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422)
                : back()->withErrors($errors);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
