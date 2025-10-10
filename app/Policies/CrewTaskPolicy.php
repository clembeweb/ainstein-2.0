<?php

namespace App\Policies;

use App\Models\CrewTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrewTaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view tasks
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CrewTask $crewTask): bool
    {
        // User can view tasks if they can view the parent crew
        if (!$crewTask->crew) {
            return false;
        }

        return $user->tenant_id === $crewTask->crew->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tasks are created as part of crew management
        // Only admins and owners can create tasks
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CrewTask $crewTask): bool
    {
        // User can update tasks if they can manage the parent crew
        if (!$crewTask->crew) {
            return false;
        }

        $crew = $crewTask->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CrewTask $crewTask): bool
    {
        // User can delete tasks if they can manage the parent crew
        if (!$crewTask->crew) {
            return false;
        }

        $crew = $crewTask->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CrewTask $crewTask): bool
    {
        // Same rules as delete
        return $this->delete($user, $crewTask);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CrewTask $crewTask): bool
    {
        // Only tenant owners can force delete tasks
        if (!$crewTask->crew) {
            return false;
        }

        return $user->tenant_id === $crewTask->crew->tenant_id &&
               $user->role === 'owner';
    }

    /**
     * Determine whether the user can reorder tasks
     */
    public function reorder(User $user, CrewTask $crewTask): bool
    {
        // User can reorder tasks if they can manage the parent crew
        if (!$crewTask->crew) {
            return false;
        }

        $crew = $crewTask->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine whether the user can assign agent to task
     */
    public function assignAgent(User $user, CrewTask $crewTask): bool
    {
        // User can assign agents if they can manage the parent crew
        if (!$crewTask->crew) {
            return false;
        }

        $crew = $crewTask->crew;

        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }
}
