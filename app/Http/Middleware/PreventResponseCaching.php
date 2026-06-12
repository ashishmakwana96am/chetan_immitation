<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class PreventResponseCaching
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            Cache::flush();

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('optimize:clear');
            
            Artisan::call('event:clear');
            Artisan::call('queue:clear');
            
            if (file_exists(base_path('bootstrap/cache/services.php'))) {
                @unlink(base_path('bootstrap/cache/services.php'));
            }
            if (file_exists(base_path('bootstrap/cache/packages.php'))) {
                @unlink(base_path('bootstrap/cache/packages.php'));
            }
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
