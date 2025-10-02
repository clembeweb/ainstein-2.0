@extends('emails.layout')

@section('title', 'Verify Your Email Address')
@section('header', 'Email Verification')

@section('content')
<h2>Please verify your email address 📧</h2>

<p>
    Hello <strong>{{ $user->name }}</strong>,
</p>

<p>
    Thank you for registering with <strong>Ainstein Platform</strong>! To complete your account setup
    and ensure you can receive important notifications, please verify your email address.
</p>

<div style="background-color: #fef3c7; padding: 20px; border-radius: 6px; border-left: 4px solid #f59e0b; margin: 24px 0;">
    <h3 style="color: #92400e; margin: 0 0 8px 0; font-size: 18px;">🔒 Account Security</h3>
    <p style="margin: 0; color: #451a03;">
        Email verification helps us ensure the security of your account and enables us to send you
        important updates about your content generation activities.
    </p>
</div>

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ $verificationUrl }}" class="button" style="font-size: 16px; padding: 16px 32px;">
        ✅ Verify Email Address
    </a>
</div>

<div style="background-color: #f3f4f6; padding: 16px; border-radius: 6px; margin: 24px 0;">
    <p style="margin: 0; font-size: 14px; color: #374151;">
        <strong>Can't click the button?</strong> Copy and paste the following link into your browser:
    </p>
    <p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280; word-break: break-all; font-family: monospace;">
        {{ $verificationUrl }}
    </p>
</div>

<h3>🔐 What happens after verification:</h3>
<ul style="padding-left: 20px;">
    <li>Your account will be fully activated</li>
    <li>You'll receive email notifications for content generation completion</li>
    <li>You'll get important security and platform updates</li>
    <li>You'll be able to reset your password if needed</li>
</ul>

<div style="background-color: #fef2f2; padding: 16px; border-radius: 6px; border-left: 4px solid #ef4444; margin: 24px 0;">
    <h4 style="color: #dc2626; margin: 0 0 8px 0;">⏰ Important Notice</h4>
    <p style="margin: 0; color: #7f1d1d; font-size: 14px;">
        This verification link will expire in 60 minutes for security reasons.
        If you didn't request this verification, you can safely ignore this email.
    </p>
</div>

<p>
    If you have any questions or need assistance, please don't hesitate to contact our support team.
    We're here to help you get started with AI-powered content generation!
</p>

<div style="text-align: center; margin: 24px 0;">
    <a href="{{ $loginUrl }}" class="button button-secondary">
        🔑 Back to Login
    </a>
</div>

<p style="color: #6b7280; font-size: 14px; margin-top: 32px;">
    Best regards,<br>
    <strong>The Ainstein Platform Team</strong>
</p>
@endsection