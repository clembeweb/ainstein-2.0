<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => function() {
            $settings = \App\Models\PlatformSetting::first();
            // Use google_client_id for SOCIAL LOGIN (user authentication)
            return $settings?->google_client_id ?: env('GOOGLE_CLIENT_ID');
        },
        'client_secret' => function() {
            $settings = \App\Models\PlatformSetting::first();
            // Use google_client_secret for SOCIAL LOGIN (user authentication)
            return $settings?->google_client_secret ?: env('GOOGLE_CLIENT_SECRET');
        },
        'redirect' => env('APP_URL') . '/auth/google/callback',
    ],

    'facebook' => [
        'client_id' => function() {
            $settings = \App\Models\PlatformSetting::first();
            // Use facebook_client_id for SOCIAL LOGIN (user authentication)
            return $settings?->facebook_client_id ?: env('FACEBOOK_CLIENT_ID');
        },
        'client_secret' => function() {
            $settings = \App\Models\PlatformSetting::first();
            // Use facebook_client_secret for SOCIAL LOGIN (user authentication)
            return $settings?->facebook_client_secret ?: env('FACEBOOK_CLIENT_SECRET');
        },
        'redirect' => env('APP_URL') . '/auth/facebook/callback',
    ],

    'openai' => [
        'api_key' => function() {
            $settings = \App\Models\PlatformSetting::first();
            return $settings?->openai_api_key ?: env('OPENAI_API_KEY');
        },
        'model' => function() {
            $settings = \App\Models\PlatformSetting::first();
            return $settings?->openai_model ?: env('OPENAI_MODEL', 'gpt-3.5-turbo');
        },
    ],

    'stripe' => [
        'key' => function() {
            $settings = \App\Models\PlatformSetting::first();
            return $settings?->stripe_public_key ?: env('STRIPE_KEY');
        },
        'secret' => function() {
            $settings = \App\Models\PlatformSetting::first();
            return $settings?->stripe_secret_key ?: env('STRIPE_SECRET');
        },
    ],

];
