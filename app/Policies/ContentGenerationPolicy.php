<?php

namespace App\Policies;

use App\Models\ContentGeneration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContentGenerationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any content generations
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view content generations list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific content generation
     */
    public function view(User $user, ContentGeneration $generation): bool
    {
        // User can only view content generations from their own tenant
        return $user->tenant_id === $generation->tenant_id;
    }

    /**
     * Determine if user can create content generations
     */
    public function create(User $user): bool
    {
        // All authenticated users with tenant can create content generations
        // Check if tenant has remaining tokens
        if ($user->tenant) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine if user can update a content generation
     */
    public function update(User $user, ContentGeneration $generation): bool
    {
        // User can only update content generations from their own tenant
        // Prevent updating published content unless admin
        if ($generation->status === 'published' &&
            !in_array($user->role, ['owner', 'admin'])) {
            return false;
        }

        return $user->tenant_id === $generation->tenant_id;
    }

    /**
     * Determine if user can delete a content generation
     */
    public function delete(User $user, ContentGeneration $generation): bool
    {
        // Cannot delete published content unless admin/owner
        if ($generation->status === 'published' &&
            !in_array($user->role, ['owner', 'admin'])) {
            return false;
        }

        // User can only delete content generations from their own tenant
        return $user->tenant_id === $generation->tenant_id;
    }

    /**
     * Determine if user can regenerate content
     * This is more restrictive as it consumes tokens
     */
    public function regenerate(User $user, ContentGeneration $generation): bool
    {
        // Must be same tenant
        if ($user->tenant_id !== $generation->tenant_id) {
            return false;
        }

        // Check if tenant has enough tokens
        if ($user->tenant) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine if user can publish content generation
     */
    public function publish(User $user, ContentGeneration $generation): bool
    {
        // Only admins and owners can publish
        return $user->tenant_id === $generation->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }
}
