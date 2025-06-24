<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.dashboard.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.dashboard.index'));
    }
}
