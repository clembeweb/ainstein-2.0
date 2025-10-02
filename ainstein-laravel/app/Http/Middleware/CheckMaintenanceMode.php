<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = PlatformSetting::first();

        // If maintenance mode is enabled and user is not super admin
        if ($settings?->maintenance_mode && !$request->user()?->is_super_admin) {

            // Allow API health checks
            if ($request->is('api/health')) {
                return $next($request);
            }

            // Return maintenance response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Platform is currently under maintenance. Please try again later.',
                    'status' => 'maintenance'
                ], 503);
            }

            // Return maintenance page for web requests
            return response()->view('maintenance', [
                'platformName' => $settings->platform_name ?? 'Platform'
            ], 503);
        }

        return $next($request);
    }
}
