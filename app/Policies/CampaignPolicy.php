<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-campaigns') || $user->can('manage-campaigns');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can('view-campaigns') || $user->can('manage-campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-campaigns');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('manage-campaigns');
    }

    public function start(User $user, Campaign $campaign): bool
    {
        return $user->can('manage-campaigns');
    }

    public function pause(User $user, Campaign $campaign): bool
    {
        return $user->can('manage-campaigns');
    }

    public function assignVendors(User $user, Campaign $campaign): bool
    {
        return $user->can('manage-campaigns');
    }

    public function generateEmails(User $user, Campaign $campaign): bool
    {
        return $user->can('manage-campaigns');
    }
}
