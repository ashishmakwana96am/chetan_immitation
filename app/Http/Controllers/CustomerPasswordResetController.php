<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerPasswordResetController extends Controller
{
    /**
     * Send OTP to the customer's email.
     */
    public function sendOtp(Request $request)
    {
        $request->merge([
            'email' => $request->email ? strtolower(trim($request->email)) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('email', $request->email)
            ->where('is_website', true)
            ->first();

        if ($customer && $customer->status == Customer::STATUS_INACTIVE) {
            return response()->json([
                'status' => 'error',
                'errors' => ['email' => ['Your account has been deactivated. Please contact support.']],
            ], 422);
        }

        if ($customer) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $customer->update([
                'otp'            => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]);

            try {
                Mail::to($customer->email)->send(new PasswordResetOtpMail($otp, $customer->name));
            } catch (\Throwable $e) {
                logger()->error('OTP email failed: ' . $e->getMessage());
            }
        }

        session(['otp_pending_email' => $request->email]);

        return response()->json([
            'status'       => 'success',
            'redirect_url' => route('otp-verification'),
        ]);
    }

    /**
     * Verify the OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->merge([
            'email' => $request->email ? strtolower(trim($request->email)) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp'   => ['required', 'string', 'size:6'],
        ], [
            'email.required' => 'Email address is required.',
            'otp.required'   => 'Please enter the OTP.',
            'otp.size'       => 'OTP must be 6 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || $customer->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'errors' => ['otp' => ['Invalid OTP. Please try again.']],
            ], 422);
        }

        if (!$customer->otp_expires_at || now()->isAfter($customer->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['otp' => ['OTP has expired. Please request a new one.']],
            ], 422);
        }

        $resetToken = Str::random(64);
        session([
            'pw_reset_email' => $customer->email,
            'pw_reset_token' => $resetToken,
        ]);

        $customer->update(['otp' => null, 'otp_expires_at' => null]);

        session()->forget('otp_pending_email');

        return response()->json([
            'status'       => 'success',
            'redirect_url' => route('customer.reset-password'),
        ]);
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $request->merge([
            'email' => $request->email ? strtolower(trim($request->email)) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::where('email', $request->email)
            ->where('is_website', true)
            ->first();

        if ($customer) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $customer->update([
                'otp'            => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]);

            try {
                Mail::to($customer->email)->send(new PasswordResetOtpMail($otp, $customer->name));
            } catch (\Throwable $e) {
                logger()->error('OTP resend email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'A new OTP has been sent to your email.',
        ]);
    }

    /**
     * Show reset password form (guarded by session token).
     */
    public function showResetForm()
    {
        if (!session('pw_reset_email') || !session('pw_reset_token')) {
            return redirect()->route('forgot-password')
                ->with('error', 'Session expired. Please start again.');
        }

        return view('website.reset-password');
    }

    /**
     * Update the password.
     */
    public function resetPassword(Request $request)
    {
        if (!session('pw_reset_email') || !session('pw_reset_token')) {
            return response()->json([
                'status' => 'error',
                'errors' => ['password' => ['Session expired. Please start over.']],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_#^()\-+=])[A-Za-z\d@$!%*?&_#^()\-+=]{8,}$/',
            ],
            'password_confirmation' => ['required'],
        ], [
            'password.required'              => 'New password is required.',
            'password.min'                   => 'Password must be at least 8 characters.',
            'password.confirmed'             => 'Passwords do not match.',
            'password.regex'                 => 'Password must contain at least 1 uppercase, 1 lowercase, 1 digit, and 1 special character.',
            'password_confirmation.required' => 'Please confirm your new password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('email', session('pw_reset_email'))->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'errors' => ['password' => ['Account not found. Please start again.']],
            ], 422);
        }

        $customer->update(['password' => $request->password]);
        session()->forget(['pw_reset_email', 'pw_reset_token']);

        return response()->json([
            'status'       => 'success',
            'redirect_url' => route('login') . '?reset=1',
        ]);
    }
}
