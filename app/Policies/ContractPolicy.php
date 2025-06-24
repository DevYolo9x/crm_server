<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy  extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.contracts.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.contracts.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.contracts.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.contracts.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.contracts.destroy'));
    }
}
