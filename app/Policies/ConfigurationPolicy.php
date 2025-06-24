<?php

namespace App\Policies;

use App\Models\Configuration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConfigurationPolicy extends BasePolicy
{
    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.configurations.edit'));
    }
}
