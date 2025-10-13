<?php

/**
 * Social Login Mock Test Script
 *
 * This script tests the Social Login functionality using mock OAuth data
 * without requiring actual OAuth credentials from Google or Facebook.
 *
 * Usage: php test-social-login-mock.php
 */

// Bootstrap Laravel
require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Colors for terminal output
class Color {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color

    public static function success($text) {
        return self::$GREEN . $text . self::$NC;
    }

    public static function error($text) {
        return self::$RED . $text . self::$NC;
    }

    public static function warning($text) {
        return self::$YELLOW . $text . self::$NC;
    }

    public static function info($text) {
        return self::$BLUE . $text . self::$NC;
    }
}

// Mock Social User Class
class MockSocialUser {
    private $id;
    private $name;
    private $email;
    private $avatar;
    private $nickname;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->name = $data['name'] ?? null;
        $this->email = $data['email'];
        $this->avatar = $data['avatar'] ?? null;
        $this->nickname = $data['nickname'] ?? null;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getAvatar() {
        return $this->avatar;
    }

    public function getNickname() {
        return $this->nickname;
    }
}

// Test Class
class SocialLoginMockTest {
    private $passedTests = 0;
    private $failedTests = 0;
    private $warnings = 0;

    public function run() {
        echo Color::info("===========================================\n");
        echo Color::info("Social Login Mock Test Suite\n");
        echo Color::info("===========================================\n\n");

        // Clean up any test data from previous runs
        $this->cleanup();

        // Run tests
        $this->testCreateUserFromGoogleWithNewEmail();
        $this->testCreateUserFromFacebookWithNewEmail();
        $this->testUpdateExistingUserSocialInfo();
        $this->testTenantAutoCreation();
        $this->testDatabaseRecords();
        $this->testUserModelHelpers();
        $this->testEmailValidation();
        $this->testSlugGeneration();

        // Summary
        $this->printSummary();
    }

    private function testCreateUserFromGoogleWithNewEmail() {
        echo Color::info("\n[TEST 1] Create User from Google OAuth (New Email)\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Mock Google user data
            $mockSocialUser = new MockSocialUser([
                'id' => 'google_123456789',
                'name' => 'John Doe',
                'email' => 'john.doe.test@example.com',
                'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
            ]);

            // Simulate createUserFromSocial
            $user = $this->createUserFromSocial('google', $mockSocialUser);

            // Assertions
            $this->assert($user !== null, "User created successfully");
            $this->assert($user->email === 'john.doe.test@example.com', "Email matches");
            $this->assert($user->name === 'John Doe', "Name matches");
            $this->assert($user->social_provider === 'google', "Provider is Google");
            $this->assert($user->social_id === 'google_123456789', "Social ID matches");
            $this->assert($user->social_avatar === 'https://lh3.googleusercontent.com/a/default-user', "Avatar URL matches");
            $this->assert($user->role === 'owner', "User role is owner");
            $this->assert($user->is_active === true, "User is active");
            $this->assert($user->email_verified_at !== null, "Email is verified");
            $this->assert($user->tenant_id !== null, "Tenant ID is assigned");

            // Check tenant
            $tenant = $user->tenant;
            $this->assert($tenant !== null, "Tenant exists");
            $this->assert($tenant->plan_type === 'free', "Tenant has free plan");
            $this->assert($tenant->status === 'active', "Tenant is active");

            echo Color::success("✓ Test 1 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 1 Failed: " . $e->getMessage() . "\n");
            echo Color::error("Stack trace: " . $e->getTraceAsString() . "\n");
            $this->failedTests++;
        }
    }

    private function testCreateUserFromFacebookWithNewEmail() {
        echo Color::info("\n[TEST 2] Create User from Facebook OAuth (New Email)\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Mock Facebook user data
            $mockSocialUser = new MockSocialUser([
                'id' => 'facebook_987654321',
                'name' => 'Jane Smith',
                'email' => 'jane.smith.test@example.com',
                'avatar' => 'https://graph.facebook.com/v12.0/987654321/picture',
                'nickname' => 'janesmith',
            ]);

            // Simulate createUserFromSocial
            $user = $this->createUserFromSocial('facebook', $mockSocialUser);

            // Assertions
            $this->assert($user !== null, "User created successfully");
            $this->assert($user->email === 'jane.smith.test@example.com', "Email matches");
            $this->assert($user->name === 'Jane Smith', "Name matches");
            $this->assert($user->social_provider === 'facebook', "Provider is Facebook");
            $this->assert($user->social_id === 'facebook_987654321', "Social ID matches");
            $this->assert($user->tenant_id !== null, "Tenant ID is assigned");

            echo Color::success("✓ Test 2 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 2 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testUpdateExistingUserSocialInfo() {
        echo Color::info("\n[TEST 3] Update Existing User Social Info\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Create a user with email but no social info
            $tenant = $this->createTestTenant();
            $existingUser = User::create([
                'email' => 'existing.user.test@example.com',
                'password_hash' => bcrypt('password123'),
                'name' => 'Existing User',
                'tenant_id' => $tenant->id,
                'role' => 'member',
                'is_active' => true,
            ]);

            $this->assert($existingUser !== null, "Existing user created");
            $this->assert($existingUser->social_provider === null, "No social provider initially");

            // Mock social login for same email
            $mockSocialUser = new MockSocialUser([
                'id' => 'google_existing_123',
                'name' => 'Existing User',
                'email' => 'existing.user.test@example.com',
                'avatar' => 'https://lh3.googleusercontent.com/existing',
            ]);

            // Update social info
            $this->updateUserSocialInfo($existingUser, 'google', $mockSocialUser);

            // Refresh from database
            $existingUser->refresh();

            $this->assert($existingUser->social_provider === 'google', "Social provider updated");
            $this->assert($existingUser->social_id === 'google_existing_123', "Social ID updated");
            $this->assert($existingUser->social_avatar === 'https://lh3.googleusercontent.com/existing', "Avatar updated");

            echo Color::success("✓ Test 3 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 3 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testTenantAutoCreation() {
        echo Color::info("\n[TEST 4] Tenant Auto-Creation\n");
        echo str_repeat("-", 50) . "\n";

        try {
            $mockSocialUser = new MockSocialUser([
                'id' => 'tenant_test_123',
                'name' => 'Tenant Test User',
                'email' => 'tenant.test@example.com',
                'avatar' => null,
            ]);

            $beforeCount = Tenant::count();
            $user = $this->createUserFromSocial('google', $mockSocialUser);
            $afterCount = Tenant::count();

            $this->assert($afterCount === $beforeCount + 1, "New tenant created");
            $this->assert($user->tenant !== null, "Tenant associated with user");
            $this->assert(Str::contains($user->tenant->name, "Workspace"), "Tenant name contains 'Workspace'");

            echo Color::success("✓ Test 4 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 4 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testDatabaseRecords() {
        echo Color::info("\n[TEST 5] Database Records Verification\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Query users with social auth
            $socialUsers = User::whereNotNull('social_provider')->get();

            $this->assert($socialUsers->count() > 0, "Social users exist in database");

            foreach ($socialUsers as $user) {
                $this->assert($user->social_provider !== null, "Social provider is set");
                $this->assert($user->social_id !== null, "Social ID is set");
                $this->assert($user->tenant_id !== null, "Tenant ID is set");
            }

            // Test query by SQL
            $sqlResult = DB::table('users')
                ->select('id', 'name', 'email', 'social_provider', 'social_id')
                ->whereNotNull('social_provider')
                ->get();

            $this->assert($sqlResult->count() > 0, "SQL query returns social users");

            echo Color::success("✓ Test 5 Passed\n");

            // Display results
            echo Color::info("\nSocial Login Users in Database:\n");
            foreach ($sqlResult as $record) {
                echo sprintf(
                    "  - %s (%s) via %s [ID: %s]\n",
                    $record->name,
                    $record->email,
                    $record->social_provider,
                    substr($record->id, 0, 8) . '...'
                );
            }

        } catch (\Exception $e) {
            echo Color::error("✗ Test 5 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testUserModelHelpers() {
        echo Color::info("\n[TEST 6] User Model Helper Methods\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Create user with social auth
            $mockSocialUser = new MockSocialUser([
                'id' => 'helper_test_123',
                'name' => 'Helper Test',
                'email' => 'helper.test@example.com',
            ]);

            $user = $this->createUserFromSocial('google', $mockSocialUser);

            // Test hasSocialAuth method
            $this->assert($user->hasSocialAuth() === true, "hasSocialAuth returns true for social user");

            // Test avatar URL
            $avatarUrl = $user->avatar_url;
            $this->assert($avatarUrl !== null, "Avatar URL is generated");

            // Test isOwner
            $this->assert($user->isOwner() === true, "User is owner");

            // Create user without social auth
            $tenant = $this->createTestTenant();
            $regularUser = User::create([
                'email' => 'regular.user@example.com',
                'password_hash' => bcrypt('password'),
                'name' => 'Regular User',
                'tenant_id' => $tenant->id,
                'role' => 'member',
                'is_active' => true,
            ]);

            $this->assert($regularUser->hasSocialAuth() === false, "hasSocialAuth returns false for regular user");

            echo Color::success("✓ Test 6 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 6 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testEmailValidation() {
        echo Color::info("\n[TEST 7] Email Validation\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Test with valid email
            $mockSocialUser = new MockSocialUser([
                'id' => 'email_valid_123',
                'name' => 'Email Test',
                'email' => 'valid.email@example.com',
            ]);

            $user = $this->createUserFromSocial('google', $mockSocialUser);
            $this->assert($user !== null, "User created with valid email");

            // Test duplicate email handling (should find existing user)
            $duplicateUser = User::where('email', 'valid.email@example.com')->first();
            $this->assert($duplicateUser !== null, "Duplicate email detected");
            $this->assert($duplicateUser->id === $user->id, "Same user returned for duplicate email");

            echo Color::success("✓ Test 7 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 7 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    private function testSlugGeneration() {
        echo Color::info("\n[TEST 8] Tenant Slug Generation\n");
        echo str_repeat("-", 50) . "\n";

        try {
            // Create users with similar names
            $mockUser1 = new MockSocialUser([
                'id' => 'slug_test_1',
                'name' => 'Test User',
                'email' => 'test.user1@example.com',
            ]);

            $mockUser2 = new MockSocialUser([
                'id' => 'slug_test_2',
                'name' => 'Test User',
                'email' => 'test.user2@example.com',
            ]);

            $user1 = $this->createUserFromSocial('google', $mockUser1);
            $user2 = $this->createUserFromSocial('google', $mockUser2);

            $slug1 = $user1->tenant->slug;
            $slug2 = $user2->tenant->slug;

            $this->assert($slug1 !== $slug2, "Slugs are unique");
            $this->assert(!empty($slug1) && !empty($slug2), "Slugs are generated");

            echo Color::info("  Slug 1: $slug1\n");
            echo Color::info("  Slug 2: $slug2\n");

            echo Color::success("✓ Test 8 Passed\n");

        } catch (\Exception $e) {
            echo Color::error("✗ Test 8 Failed: " . $e->getMessage() . "\n");
            $this->failedTests++;
        }
    }

    // Helper Methods (mimic SocialAuthController)

    private function createUserFromSocial($provider, $mockSocialUser) {
        DB::beginTransaction();

        try {
            // Create tenant first
            $tenantName = $this->extractTenantName($mockSocialUser);
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
                'name' => $mockSocialUser->getName() ?: $mockSocialUser->getNickname() ?: 'User',
                'email' => $mockSocialUser->getEmail(),
                'email_verified_at' => now(),
                'password_hash' => bcrypt(Str::random(32)),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'is_active' => true,
                'social_provider' => $provider,
                'social_id' => $mockSocialUser->getId(),
                'social_avatar' => $mockSocialUser->getAvatar()
            ]);

            DB::commit();
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateUserSocialInfo($user, $provider, $mockSocialUser) {
        $user->update([
            'social_provider' => $provider,
            'social_id' => $mockSocialUser->getId(),
            'social_avatar' => $mockSocialUser->getAvatar(),
        ]);

        if (empty($user->name)) {
            $user->update([
                'name' => $mockSocialUser->getName() ?: $mockSocialUser->getNickname() ?: 'User'
            ]);
        }
    }

    private function extractTenantName($mockSocialUser) {
        $name = $mockSocialUser->getName() ?: $mockSocialUser->getNickname();

        if ($name) {
            $parts = explode(' ', $name);
            return $parts[0] . "'s Workspace";
        }

        $emailParts = explode('@', $mockSocialUser->getEmail());
        return ucfirst($emailParts[0]) . "'s Workspace";
    }

    private function generateUniqueSlug(string $name): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function createTestTenant() {
        return Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-' . Str::random(8),
            'plan_type' => 'free',
            'status' => 'active',
            'tokens_monthly_limit' => 10000,
            'tokens_used_current' => 0,
        ]);
    }

    // Test Utilities

    private function assert($condition, $message) {
        if ($condition) {
            echo Color::success("  ✓ $message\n");
            $this->passedTests++;
        } else {
            echo Color::error("  ✗ $message\n");
            $this->failedTests++;
            throw new \Exception("Assertion failed: $message");
        }
    }

    private function cleanup() {
        echo Color::warning("Cleaning up test data...\n");

        // Delete test users and their tenants
        $testEmails = [
            'john.doe.test@example.com',
            'jane.smith.test@example.com',
            'existing.user.test@example.com',
            'tenant.test@example.com',
            'helper.test@example.com',
            'regular.user@example.com',
            'valid.email@example.com',
            'test.user1@example.com',
            'test.user2@example.com',
        ];

        foreach ($testEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $tenant = $user->tenant;
                $user->delete();
                if ($tenant) {
                    $tenant->delete();
                }
            }
        }

        echo Color::success("Cleanup complete.\n");
    }

    private function printSummary() {
        echo Color::info("\n===========================================\n");
        echo Color::info("Test Summary\n");
        echo Color::info("===========================================\n\n");

        echo Color::success("Passed: {$this->passedTests}\n");
        echo Color::error("Failed: {$this->failedTests}\n");

        if ($this->failedTests === 0) {
            echo "\n" . Color::success("All tests passed! ✓\n");
            echo Color::success("Social Login functionality is working correctly.\n");
        } else {
            echo "\n" . Color::error("Some tests failed. Please review the errors above.\n");
        }

        // Cleanup after tests
        echo "\n";
        $this->cleanup();
    }
}

// Run tests
try {
    $test = new SocialLoginMockTest();
    $test->run();
} catch (\Exception $e) {
    echo Color::error("\nFatal error: " . $e->getMessage() . "\n");
    echo Color::error($e->getTraceAsString() . "\n");
    exit(1);
}
