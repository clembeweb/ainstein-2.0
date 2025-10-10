<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     * Check if the Sanctum token is still valid and not expired.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if ($request->user() && $request->user()->currentAccessToken()) {
            $token = $request->user()->currentAccessToken();

            // Check if token has expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                // Delete the expired token
                $token->delete();

                return response()->json([
                    'message' => 'Token expired. Please login again.',
                    'error' => 'token_expired'
                ], 401);
            }
        }

        return $next($request);
    }
}
