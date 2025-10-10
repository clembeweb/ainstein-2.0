<?php

namespace App\Policies;

use App\Models\CmsConnection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CmsConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any CMS connections
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view CMS connections list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific CMS connection
     */
    public function view(User $user, CmsConnection $connection): bool
    {
        // User can only view CMS connections from their own tenant
        return $user->tenant_id === $connection->tenant_id;
    }

    /**
     * Determine if user can create CMS connections
     */
    public function create(User $user): bool
    {
        // Only admins and owners can create CMS connections
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can update a CMS connection
     */
    public function update(User $user, CmsConnection $connection): bool
    {
        // User can only update CMS connections from their own tenant
        // Only admins and owners can update
        return $user->tenant_id === $connection->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can delete a CMS connection
     */
    public function delete(User $user, CmsConnection $connection): bool
    {
        // User can only delete CMS connections from their own tenant
        // Only admins and owners can delete
        return $user->tenant_id === $connection->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can test a CMS connection
     */
    public function test(User $user, CmsConnection $connection): bool
    {
        // User can test CMS connections from their own tenant
        return $user->tenant_id === $connection->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can sync with a CMS connection
     */
    public function sync(User $user, CmsConnection $connection): bool
    {
        // User can sync CMS connections from their own tenant
        // Only admins and owners can trigger sync
        return $user->tenant_id === $connection->tenant_id &&
               in_array($user->role, ['owner', 'admin']) &&
               $connection->isActive();
    }
}
