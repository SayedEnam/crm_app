<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user) && $payment->client && Client::visibleTo($user)->whereKey($payment->client_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payments.manage');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.manage') && $this->view($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }
}
