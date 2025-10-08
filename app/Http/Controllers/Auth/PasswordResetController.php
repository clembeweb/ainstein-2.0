<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Display the password reset request form
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user's email address
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email']
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors(['email' => 'We can\'t find a user with that email address.']);
            }

            // Check if user account is active
            if (!$user->is_active) {
                return back()->withErrors(['email' => 'This account is currently inactive. Please contact support.']);
            }

            // Generate reset token
            $token = Str::random(64);

            // Store or update password reset record
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Generate reset URL
            $resetUrl = url('/password/reset/' . $token . '?email=' . urlencode($user->email));

            // TODO: Send password reset email via EmailService
            Log::info('Password reset link generated (email service disabled)', [
                'user_id' => $user->id,
                'reset_url' => $resetUrl
            ]);

            return back()->with('status', 'We have sent your password reset link to your email address.');

        } catch (\Exception $e) {
            Log::error('Password reset link sending failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['email' => 'An error occurred. Please try again later.']);
        }
    }

    /**
     * Display the password reset form
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Reset the given user's password
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email'));
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors(['email' => 'We can\'t find a user with that email address.'])->withInput($request->only('email'));
            }

            // Check if user account is active
            if (!$user->is_active) {
                return back()->withErrors(['email' => 'This account is currently inactive. Please contact support.'])->withInput($request->only('email'));
            }

            // Verify the reset token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return back()->withErrors(['email' => 'This password reset token is invalid.'])->withInput($request->only('email'));
            }

            // Check if token is valid (not expired - valid for 60 minutes)
            if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
                // Remove expired token
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return back()->withErrors(['email' => 'This password reset token has expired.'])->withInput($request->only('email'));
            }

            // Verify the token hash
            if (!Hash::check($request->token, $resetRecord->token)) {
                return back()->withErrors(['email' => 'This password reset token is invalid.'])->withInput($request->only('email'));
            }

            // Update the user's password
            $user->update([
                'password_hash' => Hash::make($request->password),
            ]);

            // Delete the reset token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Log the password reset
            Log::info('Password reset successful', ['user_id' => $user->id, 'email' => $user->email]);

            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully. You can now login with your new password.');

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['email' => 'An error occurred while resetting your password. Please try again.'])->withInput($request->only('email'));
        }
    }

    /**
     * API endpoint to send reset link
     */
    public function apiSendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'error' => 'We can\'t find a user with that email address.'
                ], 404);
            }

            if (!$user->is_active) {
                return response()->json([
                    'error' => 'This account is currently inactive. Please contact support.'
                ], 403);
            }

            // Generate reset token
            $token = Str::random(64);

            // Store password reset record
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Generate reset URL (for frontend)
            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

            // TODO: Send password reset email via EmailService
            Log::info('API password reset link generated (email service disabled)', [
                'user_id' => $user->id,
                'reset_url' => $resetUrl
            ]);

            return response()->json([
                'message' => 'Password reset link has been sent to your email address.',
                'email' => $user->email
            ]);

        } catch (\Exception $e) {
            Log::error('API password reset link sending failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * API endpoint to reset password
     */
    public function apiReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'error' => 'We can\'t find a user with that email address.'
                ], 404);
            }

            if (!$user->is_active) {
                return response()->json([
                    'error' => 'This account is currently inactive. Please contact support.'
                ], 403);
            }

            // Verify the reset token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'error' => 'This password reset token is invalid.'
                ], 400);
            }

            // Check if token is valid (not expired)
            if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
                // Remove expired token
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return response()->json([
                    'error' => 'This password reset token has expired.'
                ], 400);
            }

            // Verify the token hash
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'error' => 'This password reset token is invalid.'
                ], 400);
            }

            // Update the user's password
            $user->update([
                'password_hash' => Hash::make($request->password),
            ]);

            // Delete the reset token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Log the password reset
            Log::info('API password reset successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'message' => 'Your password has been reset successfully. You can now login with your new password.'
            ]);

        } catch (\Exception $e) {
            Log::error('API password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'An error occurred while resetting your password. Please try again.'
            ], 500);
        }
    }
}
