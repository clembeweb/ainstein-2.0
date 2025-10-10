<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SocialAuthController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirectToProvider($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect('/')->with('error', 'Provider not supported');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            Log::error("Social auth redirect error for {$provider}: " . $e->getMessage());
            return redirect('/')->with('error', 'Authentication service temporarily unavailable');
        }
    }

    /**
     * Obtain the user information from the provider.
     */
    public function handleProviderCallback($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect('/')->with('error', 'Provider not supported');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            // Check if user already exists with this email
            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // Update social provider info
                $this->updateUserSocialInfo($existingUser, $provider, $socialUser);
                Auth::login($existingUser);

                return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
            }

            // Create new user and tenant
            $user = $this->createUserFromSocial($provider, $socialUser);
            Auth::login($user);

            // Send welcome email
            $this->emailService->sendWelcomeEmail($user, $user->tenant);

            return redirect('/dashboard')->with('success', 'Account created successfully! Welcome to Ainstein.');

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error("Invalid state exception for {$provider}: " . $e->getMessage());
            return redirect('/')->with('error', 'Authentication failed. Please try again.');
        } catch (\Exception $e) {
            Log::error("Social auth callback error for {$provider}: " . $e->getMessage());
            return redirect('/')->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Create a new user from social provider data
     */
    private function createUserFromSocial($provider, $socialUser)
    {
        DB::beginTransaction();

        try {
            // Create tenant first
            $tenantName = $this->extractTenantName($socialUser);
            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => $this->generateUniqueSlug($tenantName),
                'plan_type' => 'free',
                'status' => 'active',
                'tokens_monthly_limit' => 10000,
                'tokens_used_current' => 0,
                'theme_config' => [
                    'brandName' => $tenantName,
                    'primaryColor' => '#f59e0b'
                ]
            ]);

            // Create user
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
                'password_hash' => bcrypt(Str::random(32)), // Random password since using social auth
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'is_active' => true,
                'social_provider' => $provider,
                'social_id' => $socialUser->getId(),
                'social_avatar' => $socialUser->getAvatar()
            ]);

            DB::commit();

            Log::info('New user created via social auth', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'provider' => $provider,
                'email' => $user->email
            ]);

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user from social auth', [
                'provider' => $provider,
                'email' => $socialUser->getEmail(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update existing user's social information
     */
    private function updateUserSocialInfo($user, $provider, $socialUser)
    {
        $user->update([
            'social_provider' => $provider,
            'social_id' => $socialUser->getId(),
            'social_avatar' => $socialUser->getAvatar(),
        ]);

        // Update name if empty
        if (empty($user->name)) {
            $user->update([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User'
            ]);
        }
    }

    /**
     * Extract tenant name from social user data
     */
    private function extractTenantName($socialUser)
    {
        $name = $socialUser->getName() ?: $socialUser->getNickname();

        if ($name) {
            // Use first name or first part of full name
            $parts = explode(' ', $name);
            return $parts[0] . "'s Workspace";
        }

        // Fallback to email prefix
        $emailParts = explode('@', $socialUser->getEmail());
        return ucfirst($emailParts[0]) . "'s Workspace";
    }

    /**
     * Generate a unique slug for tenant
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * API endpoint for social auth redirect
     */
    public function apiRedirectToProvider($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['error' => 'Provider not supported'], 400);
        }

        try {
            $redirectUrl = Socialite::driver($provider)->redirect()->getTargetUrl();
            return response()->json([
                'redirect_url' => $redirectUrl,
                'provider' => $provider
            ]);
        } catch (\Exception $e) {
            Log::error("API social auth redirect error for {$provider}: " . $e->getMessage());
            return response()->json([
                'error' => 'Authentication service temporarily unavailable'
            ], 500);
        }
    }

    /**
     * API endpoint for handling social auth callback
     */
    public function apiHandleProviderCallback($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['error' => 'Provider not supported'], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Check if user already exists with this email
            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // Update social provider info
                $this->updateUserSocialInfo($existingUser, $provider, $socialUser);
                $user = $existingUser;
            } else {
                // Create new user and tenant
                $user = $this->createUserFromSocial($provider, $socialUser);

                // Send welcome email for new users
                $this->emailService->sendWelcomeEmail($user, $user->tenant);
            }

            // Generate API token with explicit expiration (24 hours)
            $token = $user->createToken('social-auth', ['*'], now()->addHours(24))->plainTextToken;

            return response()->json([
                'message' => $existingUser ? 'Login successful' : 'Account created successfully',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant' => [
                        'id' => $user->tenant->id,
                        'name' => $user->tenant->name,
                        'plan_type' => $user->tenant->plan_type
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("API social auth callback error for {$provider}: " . $e->getMessage());
            return response()->json([
                'error' => 'Authentication failed'
            ], 500);
        }
    }
}
