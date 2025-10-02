<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use App\Models\ContentGeneration;
use App\Mail\WelcomeEmail;
use App\Mail\EmailVerification;
use App\Mail\PasswordResetEmail;
use App\Mail\ContentGenerationComplete;
use App\Mail\AdminNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user, ?Tenant $tenant = null): bool
    {
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user, $tenant));
            Log::info('Welcome email sent', ['user_id' => $user->id, 'email' => $user->email]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send email verification
     */
    public function sendEmailVerification(User $user, string $verificationUrl): bool
    {
        try {
            Mail::to($user->email)->send(new EmailVerification($user, $verificationUrl));
            Log::info('Email verification sent', ['user_id' => $user->id, 'email' => $user->email]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $resetUrl, string $token): bool
    {
        try {
            Mail::to($user->email)->send(new PasswordResetEmail($user, $resetUrl, $token));
            Log::info('Password reset email sent', ['user_id' => $user->id, 'email' => $user->email]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send content generation notification
     */
    public function sendContentGenerationNotification(User $user, ContentGeneration $generation, bool $isSuccess = true): bool
    {
        try {
            Mail::to($user->email)->send(new ContentGenerationComplete($user, $generation, $isSuccess));
            Log::info('Content generation notification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'generation_id' => $generation->id,
                'success' => $isSuccess
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send content generation notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'generation_id' => $generation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send admin notification
     */
    public function sendAdminNotification(
        string $notificationType,
        string $title,
        string $message,
        array $data = [],
        ?User $relatedUser = null
    ): bool {
        try {
            $adminEmails = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->pluck('email')
                ->toArray();

            if (empty($adminEmails)) {
                Log::warning('No admin emails found for notification');
                return false;
            }

            Mail::to($adminEmails)->send(new AdminNotification(
                $notificationType,
                $title,
                $message,
                $data,
                $relatedUser
            ));

            Log::info('Admin notification sent', [
                'type' => $notificationType,
                'title' => $title,
                'admin_count' => count($adminEmails),
                'related_user_id' => $relatedUser?->id
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification', [
                'type' => $notificationType,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send new user notification to admins
     */
    public function notifyAdminsOfNewUser(User $user): bool
    {
        return $this->sendAdminNotification(
            'new_user',
            'New User Registration',
            "A new user has registered on the platform: {$user->name} ({$user->email})",
            [
                'user_email' => $user->email,
                'user_role' => $user->role ?? 'member',
                'tenant_name' => $user->tenant->name ?? 'N/A',
                'registration_date' => $user->created_at->format('Y-m-d H:i:s')
            ],
            $user
        );
    }

    /**
     * Send high usage alert to admins
     */
    public function notifyAdminsOfHighUsage(Tenant $tenant, int $usagePercentage): bool
    {
        return $this->sendAdminNotification(
            'high_usage',
            'High Token Usage Alert',
            "Tenant '{$tenant->name}' has reached {$usagePercentage}% of their monthly token limit.",
            [
                'tenant_name' => $tenant->name,
                'usage_percentage' => $usagePercentage,
                'tokens_used' => $tenant->tokens_used_current,
                'tokens_limit' => $tenant->tokens_monthly_limit,
                'remaining_tokens' => $tenant->tokens_monthly_limit - $tenant->tokens_used_current
            ]
        );
    }

    /**
     * Send system error notification to admins
     */
    public function notifyAdminsOfSystemError(string $errorMessage, array $errorData = []): bool
    {
        return $this->sendAdminNotification(
            'error',
            'System Error Alert',
            "A system error has occurred: {$errorMessage}",
            array_merge([
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'environment' => app()->environment()
            ], $errorData)
        );
    }

    /**
     * Send security alert to admins
     */
    public function notifyAdminsOfSecurityEvent(string $eventType, string $description, ?User $user = null): bool
    {
        return $this->sendAdminNotification(
            'security',
            "Security Alert: {$eventType}",
            $description,
            [
                'event_type' => $eventType,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ],
            $user
        );
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration(string $testEmail): array
    {
        try {
            Mail::raw('This is a test email from Ainstein Platform. If you receive this, email configuration is working correctly.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Test Email - Ainstein Platform')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return [
                'success' => true,
                'message' => 'Test email sent successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get email sending statistics
     */
    public function getEmailStats(): array
    {
        // This would typically be implemented with a proper email tracking system
        // For now, we'll return basic statistics
        return [
            'total_sent' => 0, // Would be tracked in database
            'total_failed' => 0, // Would be tracked in database
            'success_rate' => 100,
            'last_sent' => now()->subHours(2),
            'configuration_valid' => true
        ];
    }
}