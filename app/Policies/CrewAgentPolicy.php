<?php

namespace App\Policies;

use App\Models\CrewAgent;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrewAgentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view agents
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CrewAgent $crewAgent): bool
    {
        // User can view agents if they can view the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        return $user->tenant_id === $crewAgent->crew->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Agents are created as part of crew management
        // Only admins and owners can create agents
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CrewAgent $crewAgent): bool
    {
        // User can update agents if they can manage the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        $crew = $crewAgent->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CrewAgent $crewAgent): bool
    {
        // User can delete agents if they can manage the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        $crew = $crewAgent->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CrewAgent $crewAgent): bool
    {
        // Same rules as delete
        return $this->delete($user, $crewAgent);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CrewAgent $crewAgent): bool
    {
        // Only tenant owners can force delete agents
        if (!$crewAgent->crew) {
            return false;
        }

        return $user->tenant_id === $crewAgent->crew->tenant_id &&
               $user->role === 'owner';
    }

    /**
     * Determine whether the user can configure agent tools
     */
    public function configureTools(User $user, CrewAgent $crewAgent): bool
    {
        // User can configure tools if they can manage the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        $crew = $crewAgent->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can configure agent LLM settings
     */
    public function configureLLM(User $user, CrewAgent $crewAgent): bool
    {
        // User can configure LLM if they can manage the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        $crew = $crewAgent->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can reorder agents
     */
    public function reorder(User $user, CrewAgent $crewAgent): bool
    {
        // User can reorder agents if they can manage the parent crew
        if (!$crewAgent->crew) {
            return false;
        }

        $crew = $crewAgent->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }
}
