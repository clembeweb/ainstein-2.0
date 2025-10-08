<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ActivityLogService
{
    public static function log(
        string $action,
        string $entity,
        ?string $entityId = null,
        array $metadata = [],
        ?Request $request = null
    ): ActivityLog {
        $request = $request ?? request();

        return ActivityLog::create([
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => Auth::id(),
        ]);
    }

    public static function logAuth(string $action, ?string $email = null): ActivityLog
    {
        return self::log($action, 'user', Auth::id(), [
            'email' => $email ?? Auth::user()?->email,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public static function logTenantAction(string $action, $model, array $additionalData = []): ActivityLog
    {
        $metadata = array_merge([
            'tenant_id' => $model->tenant_id ?? Auth::user()?->tenant_id,
            'model_class' => get_class($model),
            'timestamp' => now()->toISOString(),
        ], $additionalData);

        return self::log(
            $action,
            strtolower(class_basename($model)),
            $model->id,
            $metadata
        );
    }

    public static function logApiCall(string $endpoint, string $method, array $metadata = []): ActivityLog
    {
        return self::log("api_{$method}", 'api_call', null, array_merge([
            'endpoint' => $endpoint,
            'method' => $method,
            'tenant_id' => Auth::user()?->tenant_id,
            'timestamp' => now()->toISOString(),
        ], $metadata));
    }

    public static function logContentGeneration(
        string $action,
        $generation,
        array $metadata = []
    ): ActivityLog {
        return self::log($action, 'content_generation', $generation->id, array_merge([
            'tenant_id' => $generation->tenant_id,
            'page_id' => $generation->page_id,
            'prompt_type' => $generation->prompt_type,
            'ai_model' => $generation->ai_model,
            'tokens_used' => $generation->tokens_used ?? 0,
            'status' => $generation->status,
            'timestamp' => now()->toISOString(),
        ], $metadata));
    }

    public static function logPlatformAction(string $action, array $metadata = []): ActivityLog
    {
        return self::log($action, 'platform', null, array_merge([
            'is_super_admin' => Auth::user()?->is_super_admin ?? false,
            'timestamp' => now()->toISOString(),
        ], $metadata));
    }
}