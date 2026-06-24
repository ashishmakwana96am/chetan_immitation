<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class ComingSoon
{
    /**
     * Block all non-admin web requests when coming_soon mode is enabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        if ((bool) Setting::getValue('coming_soon', false)) {
            return response()->view('errors.coming_soon', [], 200);
        }

        return $next($request);
    }
}
