<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any contents
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view contents list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific content
     */
    public function view(User $user, Content $content): bool
    {
        // User can only view contents from their own tenant
        return $user->tenant_id === $content->tenant_id;
    }

    /**
     * Determine if user can create contents
     */
    public function create(User $user): bool
    {
        // All authenticated users with tenant can create contents
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can update a content
     */
    public function update(User $user, Content $content): bool
    {
        // User can only update contents from their own tenant
        return $user->tenant_id === $content->tenant_id;
    }

    /**
     * Determine if user can delete a content
     */
    public function delete(User $user, Content $content): bool
    {
        // User can only delete contents from their own tenant
        // Optionally: restrict to admin/owner or creator
        return $user->tenant_id === $content->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $content->created_by === $user->id);
    }

    /**
     * Determine if user can sync content with CMS
     */
    public function sync(User $user, Content $content): bool
    {
        // Only admins and owners can trigger content sync
        return $user->tenant_id === $content->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }
}
