<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromptPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any prompts
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view prompts list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific prompt
     */
    public function view(User $user, Prompt $prompt): bool
    {
        // Users can view system prompts (global) or their own tenant's prompts
        return $prompt->is_system ||
               $prompt->is_global ||
               $user->tenant_id === $prompt->tenant_id;
    }

    /**
     * Determine if user can create prompts
     */
    public function create(User $user): bool
    {
        // All authenticated users with tenant can create prompts
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can update a prompt
     */
    public function update(User $user, Prompt $prompt): bool
    {
        // Cannot update system prompts unless super admin
        if ($prompt->is_system && !$user->is_super_admin) {
            return false;
        }

        // User can only update prompts from their own tenant
        return $user->tenant_id === $prompt->tenant_id ||
               $user->is_super_admin;
    }

    /**
     * Determine if user can delete a prompt
     */
    public function delete(User $user, Prompt $prompt): bool
    {
        // Cannot delete system prompts
        if ($prompt->is_system) {
            return false;
        }

        // User can only delete prompts from their own tenant
        return $user->tenant_id === $prompt->tenant_id;
    }

    /**
     * Determine if user can duplicate a prompt
     */
    public function duplicate(User $user, Prompt $prompt): bool
    {
        // Users can duplicate system prompts or their own tenant's prompts
        return $user->tenant_id !== null &&
               ($prompt->is_system ||
                $prompt->is_global ||
                $user->tenant_id === $prompt->tenant_id);
    }
}
