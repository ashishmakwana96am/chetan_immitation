<?php

use App\Http\Middleware\ComingSoon;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventResponseCaching;
use App\Http\Middleware\RedirectIfAdminAuthenticated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\Compilers\CompilerException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: [
            'guest_cart',
        ]);

        $middleware->alias([
            'admin.guest' => RedirectIfAdminAuthenticated::class,
            'active.user' => EnsureUserIsActive::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if (str_starts_with($request->path(), 'admin')) {
                return route('admin.login');
            }

            return route('login');
        });
        $middleware->web(append: [
            PreventResponseCaching::class,
            ComingSoon::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This action is unauthorized.',
                ], 403);
            }

            return redirect()->route('admin.dashboard');
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This action is unauthorized.',
                ], 403);
            }

            return redirect()->route('admin.dashboard');
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof CompilerException && str_contains($e->getMessage(), 'No such file or directory')) {
                File::delete(storage_path('framework/views/*.php'));
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'View cache cleared. Please retry.',
                    ], 503);
                }

                return response('View cache cleared. Please refresh.', 503)->header('Retry-After', '2');
            }

            $debug = config('app.debug', false);
            if ($debug) {
                return null;
            }

            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($statusCode === 404) {
                return response()->view('errors.404_error', [], 404);
            }

            return response()->view('errors.500_error', [], $statusCode);
        });
    })->create();
