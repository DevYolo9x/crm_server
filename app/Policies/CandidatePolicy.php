<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CandidatePolicy  extends BasePolicy
{
    public function all(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.all'));
    }
    public function index(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.index'));
    }

    public function create(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.create'));
    }

    public function edit(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.edit'));
    }

    public function destroy(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.destroy'));
    }

    public function administrator(User $user)
    {
        return $this->checkPermission($user, config('permissions.candidates.administrator'));
    }
}
