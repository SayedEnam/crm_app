<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view') && Client::visibleTo($user)->whereKey($client->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage') && $this->view($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
