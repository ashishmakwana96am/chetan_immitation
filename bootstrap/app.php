<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\PreventResponseCaching;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {
            if (str_starts_with($request->path(), 'admin')) {
                return route('admin.login');
            }

            return route('login');
        });
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

        $exceptions->render(function (\Throwable $e, Request $request) {
            $debug = config('app.debug', false);
            if ($debug) {
                return null;
            }

            $statusCode = 500;
            if ($e instanceof HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();
            }

            if ($statusCode === 404) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Resource not found.',
                    ], 404);
                }
                return response()->view('errors.404_error', [], 404);
            }

            if ($statusCode === 500) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Internal Server Error.',
                    ], 500);
                }
                return response()->view('errors.500_error', [], 500);
            }

            return null;
        });
    })->create();
