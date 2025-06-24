<?php

namespace App\Policies;

use App\Models\Industry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IndustryPolicy  extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.industries.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.industries.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.industries.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.industries.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.industries.destroy'));
    }
}
