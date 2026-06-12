<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\PreventResponseCaching;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->web(append: [
            PreventResponseCaching::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This action is unauthorized.',
                ], 403);
            }
            return redirect()->route('admin.dashboard');
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This action is unauthorized.',
                ], 403);
            }
            return redirect()->route('admin.dashboard');
        });
    })->create();
