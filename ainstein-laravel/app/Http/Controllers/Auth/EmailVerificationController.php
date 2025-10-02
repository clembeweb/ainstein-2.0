<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show email verification notice
     */
    public function notice()
    {
        return view('auth.verify-email');
    }

    /**
     * Send verification email
     */
    public function send(Request $request)
    {
        if ($request->user()->email_verified) {
            return redirect()->intended('/dashboard');
        }

        $verificationUrl = $this->generateVerificationUrl($request->user());

        $this->emailService->sendEmailVerification($request->user(), $verificationUrl);

        return back()->with('message', 'Verification link sent!');
    }

    /**
     * Verify email address
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->email), $hash)) {
            return redirect('/login')->with('error', 'Invalid verification link');
        }

        if ($user->email_verified) {
            return redirect('/dashboard')->with('info', 'Email already verified');
        }

        $user->update([
            'email_verified' => true,
            'email_verified_at' => now()
        ]);

        return redirect('/dashboard')->with('success', 'Email verified successfully!');
    }

    /**
     * Generate verification URL
     */
    protected function generateVerificationUrl(User $user): string
    {
        return url('/email/verify/' . $user->id . '/' . sha1($user->email));
    }
}
