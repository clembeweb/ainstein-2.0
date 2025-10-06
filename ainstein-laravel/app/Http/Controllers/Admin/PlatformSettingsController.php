<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PlatformSettingsController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::first() ?? new PlatformSetting();

        return view('admin.settings.index', [
            'settings' => $settings,
            'googleAdsConfigured' => PlatformSetting::isGoogleAdsConfigured(),
            'facebookConfigured' => PlatformSetting::isFacebookConfigured(),
            'googleConsoleConfigured' => PlatformSetting::isGoogleConsoleConfigured(),
            'openAiConfigured' => PlatformSetting::isOpenAiConfigured(),
            'stripeConfigured' => PlatformSetting::isStripeConfigured(),
        ]);
    }

    public function updateOAuth(Request $request)
    {
        $request->validate([
            'google_ads_client_id' => 'nullable|string',
            'google_ads_client_secret' => 'nullable|string',
            'facebook_app_id' => 'nullable|string',
            'facebook_app_secret' => 'nullable|string',
            'google_console_client_id' => 'nullable|string',
            'google_console_client_secret' => 'nullable|string',
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        $setting->update($request->only([
            'google_ads_client_id', 'google_ads_client_secret',
            'facebook_app_id', 'facebook_app_secret',
            'google_console_client_id', 'google_console_client_secret',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'OAuth settings updated successfully!');
    }

    public function updateOpenAI(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'required|string',
            'openai_organization_id' => 'nullable|string',
            'openai_default_model' => 'required|in:gpt-4,gpt-4o,gpt-4o-mini,gpt-3.5-turbo',
            'openai_max_tokens' => 'required|integer|min:100|max:4000',
            'openai_temperature' => 'required|numeric|min:0|max:2',
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        $setting->update($request->only([
            'openai_api_key', 'openai_organization_id', 'openai_default_model',
            'openai_max_tokens', 'openai_temperature',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'OpenAI settings updated successfully!');
    }

    public function updateStripe(Request $request)
    {
        $request->validate([
            'stripe_public_key' => 'required|string',
            'stripe_secret_key' => 'required|string',
            'stripe_webhook_secret' => 'nullable|string',
            'stripe_test_mode' => 'boolean',
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        $setting->update($request->only([
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_test_mode',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Stripe settings updated successfully!');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string',
            'smtp_password' => 'required|string',
            'smtp_encryption' => 'required|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        $setting->update($request->only([
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'mail_from_address', 'mail_from_name',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Email settings updated successfully!');
    }

    public function updateAdvanced(Request $request)
    {
        $request->validate([
            'cache_driver' => 'required|in:redis,file,database',
            'cache_default_ttl' => 'required|integer|min:60',
            'queue_driver' => 'required|in:redis,database,sync',
            'queue_retry_after' => 'required|integer|min:30',
            'queue_max_tries' => 'required|integer|min:1|max:10',
            'rate_limit_per_minute' => 'required|integer|min:10|max:1000',
            'rate_limit_ai_per_hour' => 'required|integer|min:10|max:10000',
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        $setting->update($request->only([
            'cache_driver', 'cache_default_ttl', 'queue_driver',
            'queue_retry_after', 'queue_max_tries',
            'rate_limit_per_minute', 'rate_limit_ai_per_hour',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Advanced settings updated successfully!');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,svg|max:2048', // 2MB max
        ]);

        $setting = PlatformSetting::first();
        if (!$setting) {
            $setting = new PlatformSetting();
            $setting->save();
        }

        // Delete old logos if exist
        if ($setting->platform_logo_path) {
            Storage::disk('public')->delete($setting->platform_logo_path);
        }
        if ($setting->platform_logo_small_path) {
            Storage::disk('public')->delete($setting->platform_logo_small_path);
        }
        if ($setting->platform_favicon_path) {
            Storage::disk('public')->delete($setting->platform_favicon_path);
        }

        // Store original logo
        $logoPath = $request->file('logo')->store('logos', 'public');

        // Create small version (64x64 for favicon/navbar)
        $image = Image::make(Storage::disk('public')->path($logoPath));
        $image->resize(64, 64, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $smallPath = 'logos/small_' . basename($logoPath);
        $image->save(Storage::disk('public')->path($smallPath));

        // Create favicon (32x32)
        $favicon = Image::make(Storage::disk('public')->path($logoPath));
        $favicon->resize(32, 32, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $faviconPath = 'logos/favicon_' . basename($logoPath);
        $favicon->save(Storage::disk('public')->path($faviconPath));

        // Update database
        $setting->update([
            'platform_logo_path' => $logoPath,
            'platform_logo_small_path' => $smallPath,
            'platform_favicon_path' => $faviconPath,
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Logo uploaded successfully!');
    }

    public function deleteLogo()
    {
        $setting = PlatformSetting::first();

        if ($setting && $setting->platform_logo_path) {
            Storage::disk('public')->delete([
                $setting->platform_logo_path,
                $setting->platform_logo_small_path,
                $setting->platform_favicon_path,
            ]);

            $setting->update([
                'platform_logo_path' => null,
                'platform_logo_small_path' => null,
                'platform_favicon_path' => null,
            ]);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Logo deleted successfully!');
    }

    public function testOpenAI()
    {
        try {
            $apiKey = PlatformSetting::get('openai_api_key');

            if (empty($apiKey)) {
                return response()->json(['success' => false, 'message' => 'OpenAI API key not configured']);
            }

            // Simple test request
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "API connection successful"']
                ],
                'max_tokens' => 10,
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'OpenAI API connection successful!']);
            }

            return response()->json(['success' => false, 'message' => 'API connection failed: ' . $response->body()]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function testStripe()
    {
        try {
            $secretKey = PlatformSetting::get('stripe_secret_key');

            if (empty($secretKey)) {
                return response()->json(['success' => false, 'message' => 'Stripe secret key not configured']);
            }

            \Stripe\Stripe::setApiKey($secretKey);
            $account = \Stripe\Account::retrieve();

            return response()->json(['success' => true, 'message' => 'Stripe connection successful! Account: ' . $account->email]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
