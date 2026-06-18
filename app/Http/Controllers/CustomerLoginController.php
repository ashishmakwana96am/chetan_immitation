<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerLoginController extends Controller
{
    public function login(Request $request)
    {
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

            return response()->json([
                'status'       => 'success',
                'redirect_url' => redirect()->intended(route('home'))->getTargetUrl(),
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

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
