<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->status == 2) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [
                        'email' => ['Please contact administrator.']
                    ]
                ], 422);
            }

            return back()->withErrors([
                'email' => 'Please contact administrator.',
            ])->onlyInput('email');
        }

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            ActivityLogger::log('Auth', 'login', Auth::guard('web')->user(), null, null, Auth::guard('web')->user()->name . ' logged in');

            if ($request->ajax()) {
                return response()->json([
                    'status'       => 'success',
                    'redirect_url' => redirect()->intended(route('admin.dashboard'))->getTargetUrl()
                ]);
            }
            return redirect()->intended(route('admin.dashboard'));
        }

        ActivityLogger::log('Auth', 'login_failed', null, null, null, 'Failed login attempt for ' . $request->email);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'error',
                'errors' => [
                    'email' => ['These credentials do not match our records.']
                ]
            ], 422);
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('web')->user();
        if ($user) {
            ActivityLogger::log('Auth', 'logout', $user, null, null, $user->name . ' logged out');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
