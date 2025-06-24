<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JobPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.jobs.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.jobs.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.jobs.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.jobs.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.jobs.destroy'));
    }
}
