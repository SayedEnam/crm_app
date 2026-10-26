<?php

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('followups.view') || $user->hasPermission('followups.manage');
    }

    public function view(User $user, FollowUp $record): bool
    {
        return $this->viewAny($user) && FollowUp::visibleTo($user)->whereKey($record->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('followups.manage');
    }

    public function update(User $user, FollowUp $record): bool
    {
        return $user->hasPermission('followups.manage') && $this->view($user, $record);
    }

    public function delete(User $user, FollowUp $record): bool
    {
        return $this->update($user, $record);
    }
}
