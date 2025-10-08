@extends('emails.layout')

@section('title', 'Reset Your Password')
@section('header', 'Password Reset')

@section('content')
<h2>Reset your password 🔐</h2>

<p>
    Hello <strong>{{ $user->name }}</strong>,
</p>

<p>
    We received a request to reset the password for your <strong>Ainstein Platform</strong> account.
    If you made this request, click the button below to set a new password.
</p>

<div style="background-color: #fef2f2; padding: 20px; border-radius: 6px; border-left: 4px solid #ef4444; margin: 24px 0;">
    <h3 style="color: #dc2626; margin: 0 0 8px 0; font-size: 18px;">🔒 Account Security Alert</h3>
    <p style="margin: 0; color: #7f1d1d;">
        Someone requested a password reset for your account. If this wasn't you, please ignore this email
        and your password will remain unchanged.
    </p>
</div>

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ $resetUrl }}" class="button" style="font-size: 16px; padding: 16px 32px;">
        🔑 Reset Password
    </a>
</div>

<div style="background-color: #f3f4f6; padding: 16px; border-radius: 6px; margin: 24px 0;">
    <p style="margin: 0; font-size: 14px; color: #374151;">
        <strong>Can't click the button?</strong> Copy and paste the following link into your browser:
    </p>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280; word-break: break-all; font-family: monospace;">
        {{ $resetUrl }}
    </p>
</div>

<h3>🛡️ Password Security Tips:</h3>
<ul style="padding-left: 20px;">
    <li>Use a combination of uppercase and lowercase letters</li>
    <li>Include numbers and special characters</li>
    <li>Make it at least 8 characters long</li>
    <li>Avoid using personal information</li>
    <li>Don't reuse passwords from other accounts</li>
</ul>

<div style="background-color: #fef3c7; padding: 16px; border-radius: 6px; border-left: 4px solid #f59e0b; margin: 24px 0;">
    <h4 style="color: #92400e; margin: 0 0 8px 0;">⏰ Important Notice</h4>
    <p style="margin: 0; color: #451a03; font-size: 14px;">
        This password reset link will expire in 60 minutes for security reasons.
        After that time, you'll need to request a new password reset if you still need to change your password.
    </p>
</div>

<div class="stats-grid" style="margin: 24px 0;">
    <div class="stat-item">
        <span class="stat-number">{{ $user->email }}</span>
        <span class="stat-label">Account Email</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">{{ now()->format('M j, Y') }}</span>
        <span class="stat-label">Request Date</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">60 min</span>
        <span class="stat-label">Link Expires</span>
    </div>
</div>

<p>
    If you continue to have problems accessing your account, please contact our support team.
    We're here to help keep your account secure!
</p>

<div style="text-align: center; margin: 24px 0;">
    <a href="{{ $loginUrl }}" class="button button-secondary">
        🔑 Back to Login
    </a>
</div>

<div style="background-color: #f0f9ff; padding: 16px; border-radius: 6px; border-left: 4px solid #3b82f6; margin: 32px 0;">
    <p style="margin: 0; color: #1e3a8a; font-size: 14px;">
        <strong>Didn't request this reset?</strong> Your account is still secure.
        Someone may have entered your email address by mistake. You can safely ignore this email.
    </p>
</div>

<p style="color: #6b7280; font-size: 14px; margin-top: 32px;">
    Best regards,<br>
    <strong>The Ainstein Platform Team</strong>
</p>
@endsection