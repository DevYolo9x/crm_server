<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.roles.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.roles.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.roles.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.roles.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.roles.destroy'));
    }
}
