<?php

namespace App\Policies;

use App\Models\CrewTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrewTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view templates
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CrewTemplate $crewTemplate): bool
    {
        // System templates and public templates are visible to all
        if ($crewTemplate->is_system || $crewTemplate->is_public) {
            return true;
        }

        // User templates are only visible to same tenant
        return $user->tenant_id === $crewTemplate->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins and owners can create templates
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CrewTemplate $crewTemplate): bool
    {
        // System templates cannot be updated by regular users
        if ($crewTemplate->is_system) {
            return false;
        }

        // User can update templates from their own tenant
        // Only admins, owners, or creators can update
        return $user->tenant_id === $crewTemplate->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crewTemplate->created_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CrewTemplate $crewTemplate): bool
    {
        // System templates cannot be deleted
        if ($crewTemplate->is_system) {
            return false;
        }

        // User can delete templates from their own tenant
        // Only admins and owners can delete
        return $user->tenant_id === $crewTemplate->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CrewTemplate $crewTemplate): bool
    {
        // Same rules as delete
        return $this->delete($user, $crewTemplate);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CrewTemplate $crewTemplate): bool
    {
        // System templates cannot be force deleted
        if ($crewTemplate->is_system) {
            return false;
        }

        // Only tenant owners can force delete
        return $user->tenant_id === $crewTemplate->tenant_id &&
               $user->role === 'owner';
    }

    /**
     * Determine whether the user can use this template
     */
    public function use(User $user, CrewTemplate $crewTemplate): bool
    {
        // System templates and public templates can be used by anyone
        if ($crewTemplate->is_system || $crewTemplate->is_public) {
            return $user->tenant_id !== null;
        }

        // User templates can only be used by same tenant
        return $user->tenant_id === $crewTemplate->tenant_id;
    }

    /**
     * Determine whether the user can publish template
     */
    public function publish(User $user, CrewTemplate $crewTemplate): bool
    {
        // Only owners can publish templates as public
        return $user->tenant_id === $crewTemplate->tenant_id &&
               $user->role === 'owner';
    }
}
