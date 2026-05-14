<?php

namespace App\Policies;

use App\Models\CreditCard;
use App\Models\User;

class CreditCardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditCard $creditCard): bool
    {
        return $user->belongsToTeam($creditCard->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CreditCard $creditCard): bool
    {
        return $user->belongsToTeam($creditCard->team);
    }

    public function delete(User $user, CreditCard $creditCard): bool
    {
        return $user->belongsToTeam($creditCard->team);
    }
}
