<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-vendors') || $user->can('manage-vendors');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('view-vendors') || $user->can('manage-vendors');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-vendors');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function bulk(User $user): bool
    {
        return $user->can('manage-vendors');
    }

    public function export(User $user): bool
    {
        return $user->can('view-vendors') || $user->can('manage-vendors');
    }

    public function generateDocumentResponse(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function updateStatus(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function updatePriority(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function saveBrandApproval(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }

    public function research(User $user, Vendor $vendor): bool
    {
        return $user->can('manage-vendors');
    }
}
