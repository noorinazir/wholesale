<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-emails') || $user->can('manage-emails');
    }

    public function view(User $user, EmailTemplate $template): bool
    {
        return $user->can('view-emails') || $user->can('manage-emails');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-emails');
    }

    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->can('manage-emails');
    }
}
