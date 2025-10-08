<?php

namespace App\Services;

use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class SessionService
{
    public static function createSession(User $user, Request $request): Session
    {
        $agent = new Agent();
        $sessionId = Str::uuid();

        $deviceType = self::getDeviceType($agent);
        $location = self::getLocationFromIp($request->ip());

        return Session::create([
            'id' => $sessionId,
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceType,
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'location' => $location['full'] ?? null,
            'country' => $location['country'] ?? null,
            'city' => $location['city'] ?? null,
            'is_active' => true,
            'last_activity' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public static function updateSessionActivity(string $sessionId): void
    {
        Session::where('id', $sessionId)
            ->where('is_active', true)
            ->update(['last_activity' => now()]);
    }

    public static function revokeSession(string $sessionId): bool
    {
        return Session::where('id', $sessionId)
            ->update([
                'is_active' => false,
                'expires_at' => now(),
            ]) > 0;
    }

    public static function revokeAllUserSessions(int $userId, ?string $exceptSessionId = null): int
    {
        $query = Session::where('user_id', $userId)
            ->where('is_active', true);

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->update([
            'is_active' => false,
            'expires_at' => now(),
        ]);
    }

    public static function cleanupExpiredSessions(): int
    {
        return Session::where('expires_at', '<', now())
            ->orWhere('is_active', false)
            ->delete();
    }

    public static function getUserActiveSessions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Session::where('user_id', $userId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    public static function isSessionSuspicious(Session $session): bool
    {
        $user = $session->user;

        $recentSessions = Session::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($recentSessions->count() > 5) {
            return true;
        }

        $distinctCountries = $recentSessions->pluck('country')->filter()->unique();
        if ($distinctCountries->count() > 2) {
            return true;
        }

        $distinctIps = $recentSessions->pluck('ip_address')->unique();
        if ($distinctIps->count() > 10) {
            return true;
        }

        return false;
    }

    public static function getSessionStats(int $tenantId): array
    {
        $sessions = Session::where('tenant_id', $tenantId);

        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('is_active', true)->count(),
            'sessions_today' => $sessions->whereDate('created_at', today())->count(),
            'sessions_this_week' => $sessions->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'unique_users_today' => $sessions->whereDate('last_activity', today())->distinct('user_id')->count(),
            'device_breakdown' => $sessions->where('is_active', true)->groupBy('device_type')->selectRaw('device_type, count(*) as count')->pluck('count', 'device_type')->toArray(),
            'top_countries' => $sessions->where('is_active', true)->whereNotNull('country')->groupBy('country')->selectRaw('country, count(*) as count')->orderByDesc('count')->limit(5)->pluck('count', 'country')->toArray(),
        ];
    }

    private static function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isMobile()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    private static function getLocationFromIp(string $ip): array
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return [
                'country' => 'Local',
                'city' => 'Localhost',
                'full' => 'Localhost, Local'
            ];
        }

        return [
            'country' => null,
            'city' => null,
            'full' => null
        ];
    }
}