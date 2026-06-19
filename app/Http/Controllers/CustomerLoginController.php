<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->merge([
            'email' => $request->email ? strtolower(trim($request->email)) : null,
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = \App\Models\Customer::where('email', $request->email)->first();

        if ($customer && $customer->status == \App\Models\Customer::STATUS_INACTIVE) {
            return response()->json([
                'status' => 'error',
                'errors' => ['email' => ['Your account has been deactivated. Please contact support.']],
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $intended = $request->query('intended')
                ?? session()->pull('url.intended')
                ?? route('home');

            $customer = Auth::guard('customer')->user();
            $wishlistCount = $customer->wishlists()->count();

            $pendingWishlist = $request->session()->pull('pending_wishlist')
                ?? session()->pull('pending_wishlist')
                ?? $request->input('pending_wishlist');
            if ($pendingWishlist) {
                $data = json_decode($pendingWishlist, true);
                if ($data && isset($data['product_id'])) {
                    $existing = \App\Models\Wishlist::where('customer_id', $customer->id)
                        ->where('product_id', $data['product_id'])
                        ->first();
                    if ($existing) {
                        $existing->update([
                            'product_variant_id' => $data['product_variant_id'] ?? null,
                            'quantity'           => $data['quantity'] ?? 1,
                        ]);
                    } else {
                        \App\Models\Wishlist::create([
                            'customer_id'        => $customer->id,
                            'product_id'         => $data['product_id'],
                            'product_variant_id' => $data['product_variant_id'] ?? null,
                            'quantity'           => $data['quantity'] ?? 1,
                        ]);
                    }
                    $wishlistCount = $customer->wishlists()->count();
                }
            }

            return response()->json([
                'status'        => 'success',
                'redirect_url'  => $intended,
                'wishlist_count' => $wishlistCount,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'errors' => ['email' => ['These credentials do not match our records.']],
        ], 422);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectUrl = route('login');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'       => 'success',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)
            ->with('success', 'You have been logged out successfully.');
    }
}
