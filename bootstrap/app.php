<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Identity\Infrastructure\Http\Middleware\EnsurePasswordChanged;
use App\Modules\Identity\Infrastructure\Http\Middleware\EnsureUserIsActive;
use App\Shared\Exceptions\DomainException;
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
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
