<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any tenants
     * Only super admins can view all tenants
     */
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Determine if user can view a specific tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // Super admin can view any tenant
        if ($user->is_super_admin) {
            return true;
        }

        // User can only view their own tenant
        return $user->tenant_id === $tenant->id;
    }

    /**
     * Determine if user can create tenants
     * Only super admins can create new tenants
     */
    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Determine if user can update a tenant
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Super admin can update any tenant
        if ($user->is_super_admin) {
            return true;
        }

        // Tenant owner can update their own tenant
        return $user->tenant_id === $tenant->id &&
               $user->role === 'owner';
    }

    /**
     * Determine if user can delete a tenant
     * Only super admins can delete tenants
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Determine if user can manage tenant users
     */
    public function manageUsers(User $user, Tenant $tenant): bool
    {
        // Super admin can manage any tenant's users
        if ($user->is_super_admin) {
            return true;
        }

        // Tenant owner and admin can manage their tenant's users
        return $user->tenant_id === $tenant->id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can manage tenant settings
     */
    public function manageSettings(User $user, Tenant $tenant): bool
    {
        // Super admin can manage any tenant's settings
        if ($user->is_super_admin) {
            return true;
        }

        // Tenant owner can manage their tenant's settings
        return $user->tenant_id === $tenant->id &&
               $user->role === 'owner';
    }

    /**
     * Determine if user can manage tenant billing
     */
    public function manageBilling(User $user, Tenant $tenant): bool
    {
        // Super admin can manage any tenant's billing
        if ($user->is_super_admin) {
            return true;
        }

        // Tenant owner can manage their tenant's billing
        return $user->tenant_id === $tenant->id &&
               $user->role === 'owner';
    }

    /**
     * Determine if user can view tenant analytics
     */
    public function viewAnalytics(User $user, Tenant $tenant): bool
    {
        // Super admin can view any tenant's analytics
        if ($user->is_super_admin) {
            return true;
        }

        // Tenant admins and owners can view their tenant's analytics
        return $user->tenant_id === $tenant->id &&
               in_array($user->role, ['owner', 'admin']);
    }
}
