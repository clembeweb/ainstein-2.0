<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookService
{
    public static function trigger(string $event, array $payload, ?string $tenantId = null): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->where(function ($query) use ($event) {
                $query->where('events', 'LIKE', "%{$event}%")
                      ->orWhere('events', '*'); // Wildcard for all events
            });

        if ($tenantId) {
            $webhooks->where('tenant_id', $tenantId);
        }

        $webhooks = $webhooks->get();

        foreach ($webhooks as $webhook) {
            self::sendWebhook($webhook, $event, $payload);
        }
    }

    private static function sendWebhook(Webhook $webhook, string $event, array $payload): void
    {
        try {
            $signature = self::generateSignature($payload, $webhook->secret);

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Delivery' => \Illuminate\Support\Str::uuid(),
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, [
                    'event' => $event,
                    'data' => $payload,
                    'timestamp' => now()->toISOString(),
                    'tenant_id' => $webhook->tenant_id,
                ]);

            $success = $response->successful();

            // Log webhook delivery
            ActivityLogService::log('webhook_delivered', 'webhook', $webhook->id, [
                'event' => $event,
                'url' => $webhook->url,
                'status_code' => $response->status(),
                'success' => $success,
                'response_time' => $response->handlerStats()['total_time'] ?? null,
                'tenant_id' => $webhook->tenant_id,
            ]);

            if (!$success) {
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'url' => $webhook->url,
                    'event' => $event,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

        } catch (Exception $e) {
            // Log webhook failure
            ActivityLogService::log('webhook_failed', 'webhook', $webhook->id, [
                'event' => $event,
                'url' => $webhook->url,
                'error' => $e->getMessage(),
                'tenant_id' => $webhook->tenant_id,
            ]);

            Log::error('Webhook delivery error', [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
    }

    public static function triggerContentGenerated($contentGeneration): void
    {
        self::trigger('content.generated', [
            'id' => $contentGeneration->id,
            'page_id' => $contentGeneration->page_id,
            'prompt_type' => $contentGeneration->prompt_type,
            'status' => $contentGeneration->status,
            'tokens_used' => $contentGeneration->tokens_used,
            'ai_model' => $contentGeneration->ai_model,
            'created_at' => $contentGeneration->created_at->toISOString(),
        ], $contentGeneration->tenant_id);
    }

    public static function triggerPageCreated($page): void
    {
        self::trigger('page.created', [
            'id' => $page->id,
            'url_path' => $page->url_path,
            'keyword' => $page->keyword,
            'category' => $page->category,
            'language' => $page->language,
            'status' => $page->status,
            'created_at' => $page->created_at->toISOString(),
        ], $page->tenant_id);
    }

    public static function triggerUserRegistered($user): void
    {
        self::trigger('user.registered', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'tenant_id' => $user->tenant_id,
            'created_at' => $user->created_at->toISOString(),
        ], $user->tenant_id);
    }

    public static function triggerTokenLimitReached($tenant, $currentUsage): void
    {
        self::trigger('token.limit_reached', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'current_usage' => $currentUsage,
            'monthly_limit' => $tenant->tokens_monthly_limit,
            'usage_percentage' => round(($currentUsage / $tenant->tokens_monthly_limit) * 100, 2),
            'timestamp' => now()->toISOString(),
        ], $tenant->id);
    }

    public static function getAvailableEvents(): array
    {
        return [
            'content.generated' => 'Content Generation Completed',
            'content.failed' => 'Content Generation Failed',
            'page.created' => 'Page Created',
            'page.updated' => 'Page Updated',
            'page.deleted' => 'Page Deleted',
            'user.registered' => 'User Registered',
            'user.updated' => 'User Updated',
            'token.limit_reached' => 'Token Limit Reached',
            'token.limit_warning' => 'Token Limit Warning (80%)',
            'tenant.created' => 'Tenant Created',
            'tenant.updated' => 'Tenant Updated',
            'api.key_created' => 'API Key Created',
            'api.key_revoked' => 'API Key Revoked',
            'webhook.test' => 'Webhook Test Event',
        ];
    }
}