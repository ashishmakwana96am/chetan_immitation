<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            if ($request->ajax()) {
                return response()->json([
                    'status'       => 'success',
                    'redirect_url' => redirect()->intended(route('admin.dashboard'))->getTargetUrl()
                ]);
            }
            return redirect()->intended(route('admin.dashboard'));
        }

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
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
