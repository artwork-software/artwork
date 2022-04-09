<?php

namespace App\Providers;

use App\Models\Checklist;
use App\Models\Department;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Policies\ChecklistPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Invitation::class => InvitationPolicy::class,
        User::class => UserPolicy::class,
        Department::class => DepartmentPolicy::class,
        Project::class => ProjectPolicy::class,
        Checklist::class => ChecklistPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}
