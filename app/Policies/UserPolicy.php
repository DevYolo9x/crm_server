<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.users.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.users.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.users.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.users.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.users.destroy'));
    }
}
