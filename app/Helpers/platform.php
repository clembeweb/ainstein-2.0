<?php

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('platform_setting')) {
    /**
     * Get a platform setting value with caching
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function platform_setting(string $key, $default = null)
    {
        return Cache::remember("platform_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = PlatformSetting::first();
            return $setting->{$key} ?? $default;
        });
    }
}

if (!function_exists('platform_logo_url')) {
    /**
     * Get the platform logo URL
     *
     * @param string $size 'full'|'small'|'favicon'
     * @return string|null
     */
    function platform_logo_url(string $size = 'full'): ?string
    {
        $field = match($size) {
            'small' => 'platform_logo_small_path',
            'favicon' => 'platform_favicon_path',
            default => 'platform_logo_path',
        };

        $path = platform_setting($field);

        if ($path) {
            // Use Storage::url() since logos are stored in storage/app/public
            return \Illuminate\Support\Facades\Storage::url($path);
        }

        return null;
    }
}

if (!function_exists('platform_name')) {
    /**
     * Get the platform name
     *
     * @return string
     */
    function platform_name(): string
    {
        return platform_setting('platform_name', config('app.name', 'Ainstein Platform'));
    }
}

if (!function_exists('platform_in_maintenance')) {
    /**
     * Check if platform is in maintenance mode
     *
     * @return bool
     */
    function platform_in_maintenance(): bool
    {
        return (bool) platform_setting('maintenance_mode', false);
    }
}
