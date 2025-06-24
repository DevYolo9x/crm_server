<?php

namespace App\Policies;

use App\Models\User;

class BasePolicy
{
    protected function checkPermission(User $user, string $permission)
    {
        // if ($user->id === 1) {
        //     return true;
        // }
        return $user->checkPermissionAccess($permission);
    }
}
