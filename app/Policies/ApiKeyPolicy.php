<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiKeyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any API keys
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view API keys list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific API key
     */
    public function view(User $user, ApiKey $apiKey): bool
    {
        // User can only view API keys from their own tenant
        return $user->tenant_id === $apiKey->tenant_id;
    }

    /**
     * Determine if user can create API keys
     */
    public function create(User $user): bool
    {
        // Only admins and owners can create API keys
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can update an API key
     */
    public function update(User $user, ApiKey $apiKey): bool
    {
        // User can only update API keys from their own tenant
        // Only admins and owners can update
        return $user->tenant_id === $apiKey->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can delete an API key
     */
    public function delete(User $user, ApiKey $apiKey): bool
    {
        // User can only delete API keys from their own tenant
        // Only admins and owners can delete
        return $user->tenant_id === $apiKey->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can revoke an API key
     */
    public function revoke(User $user, ApiKey $apiKey): bool
    {
        // User can only revoke API keys from their own tenant
        // Only admins and owners can revoke
        return $user->tenant_id === $apiKey->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can activate an API key
     */
    public function activate(User $user, ApiKey $apiKey): bool
    {
        // User can only activate API keys from their own tenant
        // Only admins and owners can activate
        return $user->tenant_id === $apiKey->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can view API key usage statistics
     */
    public function viewUsage(User $user, ApiKey $apiKey): bool
    {
        // User can view usage for API keys from their own tenant
        return $user->tenant_id === $apiKey->tenant_id;
    }
}
