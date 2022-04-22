<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ChecklistPolicy;
use App\Policies\ChecklistTemplatePolicy;
use App\Policies\CommentPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\GenrePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SectorPolicy;
use App\Policies\TaskPolicy;
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
        Checklist::class => ChecklistPolicy::class,
        Task::class => TaskPolicy::class,
        Sector::class => SectorPolicy::class,
        Category::class => CategoryPolicy::class,
        Genre::class => GenrePolicy::class,
        Comment::class => CommentPolicy::class,
        ChecklistTemplate::class => ChecklistTemplatePolicy::class
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
