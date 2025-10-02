<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for authorization
        Gate::define('admin-access', function ($user) {
            return $user->is_super_admin;
        });

        Gate::define('tenant-admin', function ($user) {
            return $user->role === 'admin' || $user->is_super_admin;
        });

        Gate::define('manage-tenant', function ($user, $tenant) {
            return $user->is_super_admin || ($user->tenant_id === $tenant->id && $user->role === 'admin');
        });
    }
}