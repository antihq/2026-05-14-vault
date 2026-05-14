<?php

namespace App\Policies;

use App\Models\Password;
use App\Models\User;

class PasswordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Password $password): bool
    {
        return $user->belongsToTeam($password->team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Password $password): bool
    {
        return $user->belongsToTeam($password->team);
    }

    public function delete(User $user, Password $password): bool
    {
        return $user->belongsToTeam($password->team);
    }
}
