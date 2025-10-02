<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ContentGeneration;
use App\Services\EmailService;
use Illuminate\Console\Command;

class TestEmailSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type?} {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email system functionality';

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->argument('email');

        if (!$type) {
            $type = $this->choice('Which email type would you like to test?', [
                'all' => 'Test all email types',
                'welcome' => 'Welcome email',
                'verification' => 'Email verification',
                'password-reset' => 'Password reset',
                'content-generation' => 'Content generation notification',
                'admin-notification' => 'Admin notification',
                'config' => 'Test configuration'
            ], 'config');
        }

        if (!$email && $type !== 'config') {
            $email = $this->ask('What email address should we send the test to?', 'test@example.com');
        }

        $this->info("🧪 Testing email system...");
        $this->newLine();

        switch ($type) {
            case 'all':
                $this->testAll($email);
                break;
            case 'welcome':
                $this->testWelcomeEmail($email);
                break;
            case 'verification':
                $this->testVerificationEmail($email);
                break;
            case 'password-reset':
                $this->testPasswordResetEmail($email);
                break;
            case 'content-generation':
                $this->testContentGenerationEmail($email);
                break;
            case 'admin-notification':
                $this->testAdminNotification();
                break;
            case 'config':
                $this->testConfiguration($email);
                break;
            default:
                $this->error('Invalid email type');
                return 1;
        }

        return 0;
    }

    protected function testAll(string $email): void
    {
        $this->info("📧 Running comprehensive email test to: {$email}");
        $this->newLine();

        $tests = [
            'Configuration Test' => fn() => $this->testConfiguration($email),
            'Welcome Email' => fn() => $this->testWelcomeEmail($email),
            'Email Verification' => fn() => $this->testVerificationEmail($email),
            'Password Reset' => fn() => $this->testPasswordResetEmail($email),
            'Content Generation' => fn() => $this->testContentGenerationEmail($email),
            'Admin Notification' => fn() => $this->testAdminNotification(),
        ];

        foreach ($tests as $testName => $testFunction) {
            $this->line("Testing: {$testName}");
            $testFunction();
            $this->newLine();
        }

        $this->info("✅ All email tests completed!");
    }

    protected function testConfiguration(string $email = null): void
    {
        $testEmail = $email ?: 'test@example.com';

        $this->line("🔧 Testing email configuration...");

        $result = $this->emailService->testEmailConfiguration($testEmail);

        if ($result['success']) {
            $this->info("✅ " . $result['message']);
        } else {
            $this->error("❌ " . $result['message']);
        }
    }

    protected function testWelcomeEmail(string $email): void
    {
        $this->line("👋 Testing welcome email...");

        $user = User::firstOrCreate([
            'email' => $email
        ], [
            'name' => 'Test User',
            'password_hash' => bcrypt('password'),
            'is_active' => true
        ]);

        $success = $this->emailService->sendWelcomeEmail($user, $user->tenant);

        if ($success) {
            $this->info("✅ Welcome email sent successfully");
        } else {
            $this->error("❌ Failed to send welcome email");
        }
    }

    protected function testVerificationEmail(string $email): void
    {
        $this->line("📧 Testing email verification...");

        $user = User::firstOrCreate([
            'email' => $email
        ], [
            'name' => 'Test User',
            'password_hash' => bcrypt('password'),
            'is_active' => true
        ]);

        $verificationUrl = url('/verify-email?token=test-token-123');
        $success = $this->emailService->sendEmailVerification($user, $verificationUrl);

        if ($success) {
            $this->info("✅ Email verification sent successfully");
        } else {
            $this->error("❌ Failed to send email verification");
        }
    }

    protected function testPasswordResetEmail(string $email): void
    {
        $this->line("🔑 Testing password reset email...");

        $user = User::firstOrCreate([
            'email' => $email
        ], [
            'name' => 'Test User',
            'password_hash' => bcrypt('password'),
            'is_active' => true
        ]);

        $resetUrl = url('/password/reset?token=test-reset-token-123');
        $success = $this->emailService->sendPasswordResetEmail($user, $resetUrl, 'test-reset-token-123');

        if ($success) {
            $this->info("✅ Password reset email sent successfully");
        } else {
            $this->error("❌ Failed to send password reset email");
        }
    }

    protected function testContentGenerationEmail(string $email): void
    {
        $this->line("🤖 Testing content generation notification...");

        $user = User::firstOrCreate([
            'email' => $email
        ], [
            'name' => 'Test User',
            'password_hash' => bcrypt('password'),
            'is_active' => true
        ]);

        // Create a mock content generation
        $generation = new ContentGeneration([
            'prompt_type' => 'test-blog-article',
            'generated_content' => 'This is test generated content for the email notification system.',
            'meta_title' => 'Test Article Title',
            'meta_description' => 'Test article description for email testing',
            'tokens_used' => 150,
            'ai_model' => 'gpt-4o',
            'status' => 'completed',
            'tenant_id' => $user->tenant_id
        ]);

        // Test success notification
        $success = $this->emailService->sendContentGenerationNotification($user, $generation, true);

        if ($success) {
            $this->info("✅ Content generation success notification sent");
        } else {
            $this->error("❌ Failed to send content generation success notification");
        }

        // Test failure notification
        $generation->status = 'failed';
        $generation->error = 'Test error message for email notification';

        $success = $this->emailService->sendContentGenerationNotification($user, $generation, false);

        if ($success) {
            $this->info("✅ Content generation failure notification sent");
        } else {
            $this->error("❌ Failed to send content generation failure notification");
        }
    }

    protected function testAdminNotification(): void
    {
        $this->line("👨‍💼 Testing admin notifications...");

        // Test different types of admin notifications
        $notifications = [
            [
                'type' => 'new_user',
                'title' => 'Test New User Registration',
                'message' => 'This is a test notification for new user registration.'
            ],
            [
                'type' => 'high_usage',
                'title' => 'Test High Usage Alert',
                'message' => 'This is a test notification for high token usage.'
            ],
            [
                'type' => 'error',
                'title' => 'Test System Error',
                'message' => 'This is a test notification for system errors.'
            ],
            [
                'type' => 'security',
                'title' => 'Test Security Alert',
                'message' => 'This is a test notification for security events.'
            ]
        ];

        foreach ($notifications as $notification) {
            $success = $this->emailService->sendAdminNotification(
                $notification['type'],
                $notification['title'],
                $notification['message'],
                ['test_data' => 'This is test data', 'timestamp' => now()->toDateTimeString()]
            );

            if ($success) {
                $this->info("✅ Admin notification ({$notification['type']}) sent successfully");
            } else {
                $this->error("❌ Failed to send admin notification ({$notification['type']})");
            }
        }
    }
}
