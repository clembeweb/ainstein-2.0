<?php

namespace App\Policies;

use App\Models\Crew;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any crews
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view crews list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific crew
     */
    public function view(User $user, Crew $crew): bool
    {
        // User can only view crews from their own tenant
        return $user->tenant_id === $crew->tenant_id;
    }

    /**
     * Determine if user can create crews
     */
    public function create(User $user): bool
    {
        // Only admins and owners can create crews
        return $user->tenant_id !== null &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can update a crew
     */
    public function update(User $user, Crew $crew): bool
    {
        // User can update crews from their own tenant
        // Only admins, owners, or creators can update
        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine if user can delete a crew
     */
    public function delete(User $user, Crew $crew): bool
    {
        // User can delete crews from their own tenant
        // Only admins and owners can delete
        return $user->tenant_id === $crew->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine if user can execute a crew
     */
    public function execute(User $user, Crew $crew): bool
    {
        // Must be same tenant and crew must be active
        if ($user->tenant_id !== $crew->tenant_id || !$crew->isActive()) {
            return false;
        }

        // Check if tenant has enough tokens
        if ($user->tenant) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine if user can manage crew agents
     */
    public function manageAgents(User $user, Crew $crew): bool
    {
        // User can manage agents for crews from their own tenant
        // Only admins, owners, or creators can manage agents
        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine if user can manage crew tasks
     */
    public function manageTasks(User $user, Crew $crew): bool
    {
        // User can manage tasks for crews from their own tenant
        // Only admins, owners, or creators can manage tasks
        return $user->tenant_id === $crew->tenant_id &&
               (in_array($user->role, ['owner', 'admin']) ||
                $crew->created_by === $user->id);
    }

    /**
     * Determine if user can view crew executions
     */
    public function viewExecutions(User $user, Crew $crew): bool
    {
        // User can view executions for crews from their own tenant
        return $user->tenant_id === $crew->tenant_id;
    }
}
