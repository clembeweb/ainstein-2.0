<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check()) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    private function logRequest(Request $request, Response $response): void
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Skip certain routes to avoid log spam
        $skipRoutes = [
            'sanctum/csrf-cookie',
            'livewire/message',
            'filament/assets',
            'assets',
            '_debugbar',
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return;
            }
        }

        // Log different types of requests
        if (str_starts_with($path, 'api/')) {
            $this->logApiRequest($request, $statusCode);
        } elseif (str_starts_with($path, 'admin/')) {
            $this->logAdminRequest($request, $statusCode);
        } elseif (str_starts_with($path, 'dashboard/')) {
            $this->logDashboardRequest($request, $statusCode);
        }
    }

    private function logApiRequest(Request $request, int $statusCode): void
    {
        $endpoint = $request->path();
        $method = strtolower($request->method());

        ActivityLogService::logApiCall($endpoint, $method, [
            'status_code' => $statusCode,
            'parameters' => $request->query(),
            'success' => $statusCode < 400,
        ]);
    }

    private function logAdminRequest(Request $request, int $statusCode): void
    {
        if (!Auth::user()->is_super_admin) {
            return;
        }

        $action = $this->getActionFromRequest($request);

        ActivityLogService::logPlatformAction("admin_{$action}", [
            'path' => $request->path(),
            'method' => $request->method(),
            'status_code' => $statusCode,
        ]);
    }

    private function logDashboardRequest(Request $request, int $statusCode): void
    {
        $action = $this->getActionFromRequest($request);

        ActivityLogService::log("dashboard_{$action}", 'dashboard', null, [
            'path' => $request->path(),
            'method' => $request->method(),
            'status_code' => $statusCode,
            'tenant_id' => Auth::user()->tenant_id,
        ]);
    }

    private function getActionFromRequest(Request $request): string
    {
        $method = strtolower($request->method());
        $path = $request->path();

        if (str_contains($path, '/create') || $method === 'post') {
            return 'create';
        }

        if (str_contains($path, '/edit') || $method === 'put' || $method === 'patch') {
            return 'update';
        }

        if ($method === 'delete') {
            return 'delete';
        }

        return 'view';
    }
}