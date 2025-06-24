<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerPoliciesForModules([
            'App\Policies\DashboardPolicy' => 'dashboard',
            'App\Policies\ActivityLogPolicy' => 'activity_logs',
            'App\Policies\CandidatePolicy' => 'candidates',
            'App\Policies\ConfigurationPolicy' => 'configurations',
            'App\Policies\ContractPolicy' => 'contracts',
            'App\Policies\CustomerGroupPolicy' => 'customer_groups',
            'App\Policies\CustomerPolicy' => 'customers',
            'App\Policies\IndustryPolicy' => 'industries',
            'App\Policies\JobPolicy' => 'jobs',
            'App\Policies\UserPolicy' => 'users',
            'App\Policies\RolePolicy' => 'roles',
        ]);
    }
    protected function registerPoliciesForModules(array $policies)
    {
        foreach ($policies as $policy => $prefix) {
            Gate::define("{$prefix}_all", [$policy, 'all']);
            Gate::define("{$prefix}_index", [$policy, 'index']);
            Gate::define("{$prefix}_create", [$policy, 'create']);
            Gate::define("{$prefix}_edit", [$policy, 'edit']);
            Gate::define("{$prefix}_destroy", [$policy, 'destroy']);
            Gate::define("{$prefix}_administrator", [$policy, 'administrator']);
        }
    }
}
