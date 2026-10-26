<?php

namespace App\Policies;

use App\Models\ApplicationRecord;
use App\Models\User;

class ApplicationRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('applications.view');
    }

    public function view(User $user, ApplicationRecord $record): bool
    {
        return $user->hasPermission('applications.view') && ApplicationRecord::visibleTo($user)->whereKey($record->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('applications.manage');
    }

    public function update(User $user, ApplicationRecord $record): bool
    {
        return $user->hasPermission('applications.manage') && $this->view($user, $record);
    }

    public function delete(User $user, ApplicationRecord $record): bool
    {
        return $this->update($user, $record);
    }
}
