@extends('emails.layout')

@section('title', $title)
@section('header', 'Admin Notification')

@section('content')
@php
    $typeConfig = [
        'new_user' => ['icon' => '👥', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
        'high_usage' => ['icon' => '⚡', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
        'error' => ['icon' => '🚨', 'color' => '#ef4444', 'bg' => '#fef2f2'],
        'maintenance' => ['icon' => '🔧', 'color' => '#6b7280', 'bg' => '#f9fafb'],
        'security' => ['icon' => '🛡️', 'color' => '#dc2626', 'bg' => '#fef2f2'],
        'default' => ['icon' => '📢', 'color' => '#8b5cf6', 'bg' => '#f5f3ff']
    ];

    $config = $typeConfig[$notificationType] ?? $typeConfig['default'];
@endphp

<h2>{{ $config['icon'] }} {{ $title }}</h2>

<div style="background-color: {{ $config['bg'] }}; padding: 20px; border-radius: 6px; border-left: 4px solid {{ $config['color'] }}; margin: 24px 0;">
    <h3 style="color: {{ $config['color'] }}; margin: 0 0 8px 0; font-size: 18px;">System Alert</h3>
    <p style="margin: 0; color: #374151;">
        {{ $message }}
    </p>
</div>

@if($relatedUser)
<div style="background-color: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; margin: 24px 0;">
    <h4 style="color: #1f2937; margin: 0 0 12px 0;">👤 Related User Information</h4>
    <div style="color: #374151; font-size: 14px;">
        <p style="margin: 4px 0;"><strong>Name:</strong> {{ $relatedUser->name }}</p>
        <p style="margin: 4px 0;"><strong>Email:</strong> {{ $relatedUser->email }}</p>
        <p style="margin: 4px 0;"><strong>Role:</strong> {{ ucfirst($relatedUser->role ?? 'member') }}</p>
        @if($relatedUser->tenant)
            <p style="margin: 4px 0;"><strong>Tenant:</strong> {{ $relatedUser->tenant->name }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Created:</strong> {{ $relatedUser->created_at->format('M j, Y H:i') }}</p>
    </div>
</div>
@endif

@if(!empty($data))
<h3>📊 Additional Details:</h3>

<div class="stats-grid">
    @foreach($data as $key => $value)
        <div class="stat-item">
            @if(is_numeric($value))
                <span class="stat-number">{{ number_format($value) }}</span>
            @else
                <span class="stat-number" style="font-size: 14px;">{{ $value }}</span>
            @endif
            <span class="stat-label">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</span>
        </div>
    @endforeach
</div>
@endif

@switch($notificationType)
    @case('new_user')
        <h3>🎯 Recommended Actions:</h3>
        <ul style="padding-left: 20px;">
            <li>Review the new user's tenant configuration</li>
            <li>Verify the user's permissions and role assignment</li>
            <li>Check if the tenant needs additional resources</li>
            <li>Monitor initial usage patterns</li>
        </ul>
        @break

    @case('high_usage')
        <h3>⚠️ Immediate Actions Required:</h3>
        <ul style="padding-left: 20px;">
            <li>Review token usage patterns and trends</li>
            <li>Consider increasing tenant limits if appropriate</li>
            <li>Check for potential abuse or unusual activity</li>
            <li>Contact the tenant to discuss upgrade options</li>
        </ul>
        @break

    @case('error')
        <h3>🔧 Troubleshooting Steps:</h3>
        <ul style="padding-left: 20px;">
            <li>Check system logs for detailed error information</li>
            <li>Verify all external service connections (OpenAI, etc.)</li>
            <li>Monitor system resources and performance</li>
            <li>Consider temporary maintenance mode if critical</li>
        </ul>
        @break

    @case('security')
        <h3>🛡️ Security Actions:</h3>
        <ul style="padding-left: 20px;">
            <li>Investigate the security event immediately</li>
            <li>Review user access logs and activities</li>
            <li>Consider temporary account restrictions if needed</li>
            <li>Document the incident for security review</li>
        </ul>
        @break

    @case('maintenance')
        <h3>🔧 Maintenance Tasks:</h3>
        <ul style="padding-left: 20px;">
            <li>Review system performance metrics</li>
            <li>Check for pending updates and patches</li>
            <li>Verify backup integrity and schedules</li>
            <li>Monitor system health after maintenance</li>
        </ul>
        @break
@endswitch

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ $adminUrl }}" class="button" style="font-size: 16px; padding: 16px 32px;">
        🎯 Open Admin Panel
    </a>
    <br>
    <a href="{{ $dashboardUrl }}" class="button button-secondary" style="margin-top: 12px;">
        📊 View Dashboard
    </a>
</div>

<div style="background-color: #fef3c7; padding: 16px; border-radius: 6px; margin: 24px 0;">
    <h4 style="color: #92400e; margin: 0 0 8px 0;">💡 System Information</h4>
    <div style="color: #451a03; font-size: 14px;">
        <p style="margin: 4px 0;"><strong>Platform:</strong> Ainstein Platform</p>
        <p style="margin: 4px 0;"><strong>Environment:</strong> {{ app()->environment() }}</p>
        <p style="margin: 4px 0;"><strong>Timestamp:</strong> {{ now()->format('Y-m-d H:i:s T') }}</p>
        <p style="margin: 4px 0;"><strong>Notification Type:</strong> {{ ucwords(str_replace('_', ' ', $notificationType)) }}</p>
    </div>
</div>

<p>
    This notification was automatically generated by the Ainstein Platform monitoring system.
    Please review and take appropriate action as needed.
</p>

<p style="color: #6b7280; font-size: 14px; margin-top: 32px;">
    System Administrator Alert,<br>
    <strong>Ainstein Platform Monitoring</strong>
</p>
@endsection