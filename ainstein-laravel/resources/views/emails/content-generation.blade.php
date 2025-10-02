@extends('emails.layout')

@section('title', $isSuccess ? 'Content Generation Complete' : 'Content Generation Failed')
@section('header', $isSuccess ? 'Generation Complete!' : 'Generation Failed')

@section('content')
@if($isSuccess)
    <h2>Your content is ready! ✅</h2>

    <p>
        Great news, <strong>{{ $user->name }}</strong>! Your AI content generation has been completed successfully.
    </p>

    <div style="background-color: #f0fdf4; padding: 20px; border-radius: 6px; border-left: 4px solid #22c55e; margin: 24px 0;">
        <h3 style="color: #166534; margin: 0 0 8px 0; font-size: 18px;">🎯 Generation Summary</h3>
        <div style="color: #14532d;">
            @if($generation->page)
                <p style="margin: 4px 0;"><strong>Page:</strong> {{ $generation->page->url_path }}</p>
            @endif
            <p style="margin: 4px 0;"><strong>Prompt Type:</strong> {{ $generation->prompt_type }}</p>
            <p style="margin: 4px 0;"><strong>AI Model:</strong> {{ $generation->ai_model }}</p>
            <p style="margin: 4px 0;"><strong>Tokens Used:</strong> {{ number_format($generation->tokens_used) }}</p>
        </div>
    </div>

@else
    <h2>Content generation failed ❌</h2>

    <p>
        We're sorry, <strong>{{ $user->name }}</strong>. Your AI content generation encountered an issue and could not be completed.
    </p>

    <div style="background-color: #fef2f2; padding: 20px; border-radius: 6px; border-left: 4px solid #ef4444; margin: 24px 0;">
        <h3 style="color: #dc2626; margin: 0 0 8px 0; font-size: 18px;">❌ Generation Details</h3>
        <div style="color: #7f1d1d;">
            @if($generation->page)
                <p style="margin: 4px 0;"><strong>Page:</strong> {{ $generation->page->url_path }}</p>
            @endif
            <p style="margin: 4px 0;"><strong>Prompt Type:</strong> {{ $generation->prompt_type }}</p>
            <p style="margin: 4px 0;"><strong>AI Model:</strong> {{ $generation->ai_model }}</p>
            @if($generation->error)
                <p style="margin: 4px 0;"><strong>Error:</strong> {{ $generation->error }}</p>
            @endif
        </div>
    </div>
@endif

<div class="stats-grid">
    <div class="stat-item">
        <span class="stat-number">{{ $generation->created_at->format('M j') }}</span>
        <span class="stat-label">Generated Date</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">{{ $generation->created_at->format('H:i') }}</span>
        <span class="stat-label">Generated Time</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">{{ number_format($generation->tokens_used ?: 0) }}</span>
        <span class="stat-label">Tokens Used</span>
    </div>
    @if($generation->page)
    <div class="stat-item">
        <span class="stat-number">{{ $generation->page->keyword ?: 'N/A' }}</span>
        <span class="stat-label">Target Keyword</span>
    </div>
    @endif
</div>

@if($isSuccess)
    <h3>📝 What you can do now:</h3>
    <ul style="padding-left: 20px;">
        <li><strong>Review the Content</strong> - Check the generated content for quality and relevance</li>
        <li><strong>Edit if Needed</strong> - Make any necessary adjustments to match your style</li>
        <li><strong>Publish or Save</strong> - Use the content on your website or save it for later</li>
        <li><strong>Generate More</strong> - Create additional content using different prompts</li>
    </ul>

    @if($generation->generated_content)
        <div style="background-color: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; margin: 24px 0;">
            <h4 style="color: #1f2937; margin: 0 0 12px 0;">📄 Content Preview</h4>
            <div style="color: #374151; font-size: 14px; line-height: 1.6;">
                {{ Str::limit($generation->generated_content, 300) }}
                @if(strlen($generation->generated_content) > 300)
                    <p style="margin: 8px 0 0 0; color: #6b7280; font-style: italic;">
                        ...and {{ strlen($generation->generated_content) - 300 }} more characters.
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $generationUrl }}" class="button" style="font-size: 16px; padding: 16px 32px;">
            📖 View Full Content
        </a>
        <br>
        <a href="{{ $generationsUrl }}" class="button button-secondary" style="margin-top: 12px;">
            📋 All Generations
        </a>
    </div>

@else
    <h3>🔧 What you can do:</h3>
    <ul style="padding-left: 20px;">
        <li><strong>Try Again</strong> - The issue might be temporary</li>
        <li><strong>Check Your Prompt</strong> - Ensure all variables are properly filled</li>
        <li><strong>Verify Token Balance</strong> - Make sure you have sufficient tokens</li>
        <li><strong>Contact Support</strong> - If the problem persists, we're here to help</li>
    </ul>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $generationsUrl }}" class="button">
            🔄 Try Again
        </a>
        <br>
        <a href="{{ $dashboardUrl }}" class="button button-secondary" style="margin-top: 12px;">
            🏠 Dashboard
        </a>
    </div>
@endif

<div style="background-color: #fef3c7; padding: 16px; border-radius: 6px; margin: 24px 0;">
    <h4 style="color: #92400e; margin: 0 0 8px 0;">💡 Pro Tip</h4>
    <p style="margin: 0; color: #451a03;">
        @if($isSuccess)
            Customize your prompts to get even better results! Try different variables and prompt templates
            to create content that perfectly matches your needs.
        @else
            Most generation failures are due to temporary API issues or insufficient tokens.
            Check your account balance and try again in a few minutes.
        @endif
    </p>
</div>

<p style="color: #6b7280; font-size: 14px; margin-top: 32px;">
    Best regards,<br>
    <strong>The Ainstein Platform Team</strong>
</p>
@endsection