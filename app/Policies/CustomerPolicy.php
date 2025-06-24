<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.customers.all'));
    }

    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.customers.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.customers.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.customers.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.customers.destroy'));
    }
}
