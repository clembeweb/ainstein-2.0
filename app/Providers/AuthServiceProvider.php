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
        \App\Models\AdvCampaign::class => \App\Policies\AdvCampaignPolicy::class,
        \App\Models\Page::class => \App\Policies\PagePolicy::class,
        \App\Models\Prompt::class => \App\Policies\PromptPolicy::class,
        \App\Models\ContentGeneration::class => \App\Policies\ContentGenerationPolicy::class,
        \App\Models\ApiKey::class => \App\Policies\ApiKeyPolicy::class,
        \App\Models\Content::class => \App\Policies\ContentPolicy::class,
        \App\Models\Crew::class => \App\Policies\CrewPolicy::class,
        \App\Models\CmsConnection::class => \App\Policies\CmsConnectionPolicy::class,
        \App\Models\Tenant::class => \App\Policies\TenantPolicy::class,
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