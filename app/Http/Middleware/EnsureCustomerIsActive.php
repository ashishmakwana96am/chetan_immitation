<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && (int)$customer->status !== Customer::STATUS_ACTIVE) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'These credentials do not match our records.',
                    'redirect_url' => route('login')
                ], 401);
            }

            return redirect()
                ->route('login')
                ->with('error', 'These credentials do not match our records.');
        }

        return $next($request);
    }
}
