@extends('emails.layout')

@section('title', 'Welcome to Ainstein Platform')
@section('header', 'Welcome to Ainstein!')

@section('content')
<h2>Welcome, {{ $user->name }}! 🎉</h2>

<p>
    We're thrilled to have you join <strong>Ainstein Platform</strong>, the cutting-edge AI-powered content generation system.
    Your account has been successfully created and you're ready to start creating amazing content!
</p>

@if($tenant)
<div style="background-color: #f0f9ff; padding: 20px; border-radius: 6px; border-left: 4px solid #3b82f6; margin: 24px 0;">
    <h3 style="color: #1e40af; margin: 0 0 8px 0; font-size: 18px;">🏢 Your Organization</h3>
    <p style="margin: 0; font-weight: 600;">{{ $tenant->name }}</p>
    <p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;">
        Token Limit: {{ number_format($tenant->tokens_monthly_limit) }} tokens/month
    </p>
</div>
@endif

<h3>🚀 What you can do now:</h3>
<ul style="padding-left: 20px;">
    <li><strong>Create Pages</strong> - Set up your content structure with SEO optimization</li>
    <li><strong>Manage Prompts</strong> - Use our AI prompt library or create your own</li>
    <li><strong>Generate Content</strong> - Leverage AI to create high-quality, engaging content</li>
    <li><strong>Track Usage</strong> - Monitor your token consumption and generation statistics</li>
    <li><strong>API Integration</strong> - Connect your applications with our powerful API</li>
</ul>

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ $dashboardUrl }}" class="button">
        🎯 Access Your Dashboard
    </a>
    <br>
    <a href="{{ $loginUrl }}" class="button button-secondary" style="margin-top: 12px;">
        🔑 Login Page
    </a>
</div>

<div style="background-color: #fef3c7; padding: 16px; border-radius: 6px; margin: 24px 0;">
    <h4 style="color: #92400e; margin: 0 0 8px 0;">💡 Pro Tip</h4>
    <p style="margin: 0; color: #451a03;">
        Start by exploring our system prompts library - we've pre-configured templates for
        blog articles, SEO content, social media posts, and much more!
    </p>
</div>

<h3>🎯 Your Account Details:</h3>
<div class="stats-grid">
    <div class="stat-item">
        <span class="stat-number">{{ $user->email }}</span>
        <span class="stat-label">Email Address</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">{{ ucfirst($user->role ?? 'Member') }}</span>
        <span class="stat-label">Role</span>
    </div>
    @if($tenant)
    <div class="stat-item">
        <span class="stat-number">{{ number_format($tenant->tokens_monthly_limit) }}</span>
        <span class="stat-label">Monthly Tokens</span>
    </div>
    @endif
</div>

<p>
    Need help getting started? Check out our documentation or contact our support team.
    We're here to help you succeed with AI-powered content generation!
</p>

<p style="color: #6b7280; font-size: 14px; margin-top: 32px;">
    Best regards,<br>
    <strong>The Ainstein Platform Team</strong>
</p>
@endsection