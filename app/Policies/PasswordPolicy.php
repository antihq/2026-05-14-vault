<?php

namespace App\Policies;

use App\Enums\TeamPermission;
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

    /**
     * Determine whether the user can move the password to another team.
     *
     * Requires Admin or higher on the password's current (source) team.
     * Destination team membership is validated separately in the action.
     */
    public function move(User $user, Password $password): bool
    {
        return $user->belongsToTeam($password->team)
            && $user->hasTeamPermission($password->team, TeamPermission::MovePassword);
    }
}
