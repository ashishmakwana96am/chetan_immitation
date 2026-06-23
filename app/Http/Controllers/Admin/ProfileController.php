<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Show admin password change form.
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Change admin password.
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'different:current_password'],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ], [
            'new_password.different' => 'The new password must be different from the current password.',
            'new_password_confirmation.same' => 'The confirm password must match the new password.',
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            if ($request->filled('current_password') && !Hash::check($request->current_password, $user->password)) {
                $validator->errors()->add('current_password', 'The current password you entered is incorrect.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password changed successfully.',
        ]);
    }
}
