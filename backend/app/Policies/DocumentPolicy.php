<?php

namespace App\Policies;

use App\Models\ApplicationRecord;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->viewAny($user) && $document->application && ApplicationRecord::visibleTo($user)->whereKey($document->application_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage') && $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
