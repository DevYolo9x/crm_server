<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityLogPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.activity_logs.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.activity_logs.index'));
    }
}
