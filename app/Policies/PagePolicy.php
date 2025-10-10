<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any pages
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view pages list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific page
     */
    public function view(User $user, Page $page): bool
    {
        // User can only view pages from their own tenant
        return $user->tenant_id === $page->tenant_id;
    }

    /**
     * Determine if user can create pages
     */
    public function create(User $user): bool
    {
        // All authenticated users with tenant can create pages
        // Optionally check if tenant is within plan limits
        if ($user->tenant) {
            $limits = $user->tenant->isWithinLimits();
            return $limits['pages'] ?? true;
        }

        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can update a page
     */
    public function update(User $user, Page $page): bool
    {
        // User can only update pages from their own tenant
        return $user->tenant_id === $page->tenant_id;
    }

    /**
     * Determine if user can delete a page
     */
    public function delete(User $user, Page $page): bool
    {
        // User can only delete pages from their own tenant
        // Optionally: restrict to admin/owner roles only
        return $user->tenant_id === $page->tenant_id &&
               in_array($user->role, ['owner', 'admin', 'member']);
    }

    /**
     * Determine if user can perform bulk operations on pages
     */
    public function bulkAction(User $user): bool
    {
        // Only admins and owners can perform bulk operations
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }
}
