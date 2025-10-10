<?php

namespace App\Policies;

use App\Models\CrewExecution;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrewExecutionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view execution list
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CrewExecution $crewExecution): bool
    {
        // User can only view executions from their own tenant
        return $user->tenant_id === $crewExecution->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Executions are created through crew execution, not directly
        // Check if tenant has available tokens
        if ($user->tenant && $user->tenant_id !== null) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CrewExecution $crewExecution): bool
    {
        // Executions cannot be manually updated
        // They are updated by the system during execution
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CrewExecution $crewExecution): bool
    {
        // Only admins and owners can delete executions
        return $user->tenant_id === $crewExecution->tenant_id &&
               in_array($user->role, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CrewExecution $crewExecution): bool
    {
        // Same rules as delete
        return $this->delete($user, $crewExecution);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CrewExecution $crewExecution): bool
    {
        // Only tenant owners can force delete executions
        return $user->tenant_id === $crewExecution->tenant_id &&
               $user->role === 'owner';
    }

    /**
     * Determine whether the user can cancel a running execution
     */
    public function cancel(User $user, CrewExecution $crewExecution): bool
    {
        // User can cancel if same tenant and execution is running
        if ($user->tenant_id !== $crewExecution->tenant_id) {
            return false;
        }

        // Execution must be in running or pending state
        if (!in_array($crewExecution->status, ['pending', 'running'])) {
            return false;
        }

        // Admins, owners, or the user who started the execution can cancel
        return in_array($user->role, ['owner', 'admin']) ||
               $crewExecution->executed_by === $user->id;
    }

    /**
     * Determine whether the user can retry a failed execution
     */
    public function retry(User $user, CrewExecution $crewExecution): bool
    {
        // User can retry if same tenant and execution failed
        if ($user->tenant_id !== $crewExecution->tenant_id) {
            return false;
        }

        // Execution must be in failed or cancelled state
        if (!in_array($crewExecution->status, ['failed', 'cancelled'])) {
            return false;
        }

        // Check if tenant has available tokens
        if (!$user->tenant || !$user->tenant->canGenerateContent()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view execution logs
     */
    public function viewLogs(User $user, CrewExecution $crewExecution): bool
    {
        // Same tenant users can view logs
        return $user->tenant_id === $crewExecution->tenant_id;
    }
}
