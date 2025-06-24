<?php

namespace App\Policies;

use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerGroupPolicy extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.customer_groups.all'));
    }

    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.customer_groups.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.customer_groups.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.customer_groups.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.customer_groups.destroy'));
    }
}
