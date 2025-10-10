<?php

namespace App\Policies;

use App\Models\AdvCampaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvCampaignPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any campaigns
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users with tenant can view campaigns list
        return $user->tenant_id !== null;
    }

    /**
     * Determine if user can view a specific campaign
     */
    public function view(User $user, AdvCampaign $campaign): bool
    {
        // User can only view campaigns from their own tenant
        return $user->tenant_id === $campaign->tenant_id;
    }

    /**
     * Determine if user can create campaigns
     */
    public function create(User $user): bool
    {
        // All authenticated users with tenant can create campaigns
        // Check if tenant has remaining tokens (optional)
        if ($user->tenant) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine if user can update a campaign
     */
    public function update(User $user, AdvCampaign $campaign): bool
    {
        // User can only update campaigns from their own tenant
        // Optionally: restrict to owner/admin roles
        return $user->tenant_id === $campaign->tenant_id;
    }

    /**
     * Determine if user can delete a campaign
     */
    public function delete(User $user, AdvCampaign $campaign): bool
    {
        // User can only delete campaigns from their own tenant
        // Optionally: restrict to owner/admin roles
        return $user->tenant_id === $campaign->tenant_id;
    }

    /**
     * Determine if user can regenerate campaign assets
     * This is more restrictive as it consumes tokens
     */
    public function regenerate(User $user, AdvCampaign $campaign): bool
    {
        // Must be same tenant
        if ($user->tenant_id !== $campaign->tenant_id) {
            return false;
        }

        // Check if tenant has enough tokens
        if ($user->tenant) {
            return $user->tenant->canGenerateContent();
        }

        return false;
    }

    /**
     * Determine if user can export campaign assets
     */
    public function export(User $user, AdvCampaign $campaign): bool
    {
        // User can export campaigns from their own tenant
        return $user->tenant_id === $campaign->tenant_id;
    }
}
