<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ManagePlatformSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:settings
                            {action : Action to perform (list, set, get)}
                            {key? : Setting key to get or set}
                            {value? : Value to set}
                            {--interactive : Use interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage platform settings from command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        match ($action) {
            'list' => $this->listSettings(),
            'get' => $this->getSetting(),
            'set' => $this->setSetting(),
            'setup' => $this->setupPlatform(),
            default => $this->error("Unknown action: {$action}. Use: list, get, set, setup")
        };
    }

    private function listSettings()
    {
        $settings = PlatformSetting::first();

        if (!$settings) {
            $this->warn('No platform settings configured yet. Run: php artisan platform:settings setup');
            return;
        }

        $this->info('Current Platform Settings:');
        $this->line('');

        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['Platform Name', $settings->platform_name ?: 'Not set', $settings->platform_name ? '✅' : '❌'],
                ['OpenAI API Key', $settings->openai_api_key ? '***configured***' : 'Not set', $settings->openai_api_key ? '✅' : '❌'],
                ['OpenAI Model', $settings->openai_model ?: 'gpt-4o', '✅'],
                ['Google Client ID', $settings->google_client_id ? '***configured***' : 'Not set', $settings->google_client_id ? '✅' : '❌'],
                ['Facebook Client ID', $settings->facebook_client_id ? '***configured***' : 'Not set', $settings->facebook_client_id ? '✅' : '❌'],
                ['Maintenance Mode', $settings->maintenance_mode ? 'ON' : 'OFF', $settings->maintenance_mode ? '⚠️' : '✅'],
                ['Stripe Secret Key', $settings->stripe_secret_key ? '***configured***' : 'Not set', $settings->stripe_secret_key ? '✅' : '❌'],
            ]
        );
    }

    private function getSetting()
    {
        $key = $this->argument('key');
        if (!$key) {
            $this->error('Key argument is required for get action');
            return;
        }

        $settings = PlatformSetting::first();
        if (!$settings) {
            $this->error('No platform settings found');
            return;
        }

        $value = $settings->{$key} ?? null;
        $this->info("Setting '{$key}': " . ($value ? (in_array($key, ['openai_api_key', 'google_client_secret', 'facebook_client_secret', 'stripe_secret_key']) ? '***hidden***' : $value) : 'Not set'));
    }

    private function setSetting()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (!$key) {
            $this->error('Key argument is required for set action');
            return;
        }

        $settings = PlatformSetting::firstOrCreate([]);

        if (!$value && $this->option('interactive')) {
            $value = $this->ask("Enter value for '{$key}':");
        }

        if (!$value) {
            $this->error('Value is required');
            return;
        }

        try {
            $settings->update([$key => $value]);
            $this->info("Setting '{$key}' updated successfully");
        } catch (\Exception $e) {
            $this->error("Error updating setting: " . $e->getMessage());
        }
    }

    private function setupPlatform()
    {
        $this->info('🚀 Platform Setup Wizard');
        $this->line('');

        $settings = PlatformSetting::firstOrCreate([]);

        // Platform Info
        $platformName = $this->ask('Platform Name', $settings->platform_name ?: 'Ainstein Platform');
        $platformDescription = $this->ask('Platform Description', $settings->platform_description);

        // OpenAI Configuration
        $this->line('');
        $this->info('🤖 OpenAI Configuration');
        $openaiKey = $this->secret('OpenAI API Key (sk-...)') ?: $settings->openai_api_key;
        $openaiModel = $this->choice('OpenAI Model', [
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo'
        ], $settings->openai_model ?: 'gpt-4o');

        // OAuth Configuration
        $this->line('');
        $this->info('🔐 OAuth Configuration (optional)');
        $googleClientId = $this->ask('Google Client ID') ?: $settings->google_client_id;
        $googleClientSecret = $googleClientId ? ($this->secret('Google Client Secret') ?: $settings->google_client_secret) : null;

        $facebookClientId = $this->ask('Facebook App ID') ?: $settings->facebook_client_id;
        $facebookClientSecret = $facebookClientId ? ($this->secret('Facebook App Secret') ?: $settings->facebook_client_secret) : null;

        // Update settings
        $settings->update([
            'platform_name' => $platformName,
            'platform_description' => $platformDescription,
            'openai_api_key' => $openaiKey,
            'openai_model' => $openaiModel,
            'google_client_id' => $googleClientId,
            'google_client_secret' => $googleClientSecret,
            'facebook_client_id' => $facebookClientId,
            'facebook_client_secret' => $facebookClientSecret,
        ]);

        $this->line('');
        $this->info('✅ Platform settings saved successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('• Configure Stripe for payments: php artisan platform:settings set stripe_secret_key sk_...');
        $this->line('• Set up SMTP for emails');
        $this->line('• Access admin panel: ' . url('/admin'));
    }
}
