<?php

namespace App\Providers;

use App\Enums\RoleNameEnum;
use App\Models\Category;
use App\Models\ChecklistTemplate;
use App\Models\Contract;
use App\Models\Freelancer;
use App\Models\User;
use App\Policies\AreaPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ChecklistPolicy;
use App\Policies\ChecklistTemplatePolicy;
use App\Policies\CommentPolicy;
use App\Policies\ContractPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EventPolicy;
use App\Policies\FreelancerPolicy;
use App\Policies\GenrePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SageApiSettingsPolicy;
use App\Policies\SectorPolicy;
use App\Policies\ServiceProviderPolicy;
use App\Policies\TaskTemplatePolicy;
use App\Policies\UserPolicy;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Policies\SageAssignedDataCommentPolicy;
use Artwork\Modules\Budget\Policies\SageNotAssignedDataPolicy;
use Artwork\Modules\BudgetColumnSetting\Models\BudgetColumnSetting;
use Artwork\Modules\BudgetColumnSetting\Policies\BudgetColumnSettingPolicy;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Artwork\Modules\BudgetManagementAccount\Policies\BudgetManagementAccountPolicy;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Artwork\Modules\BudgetManagementCostUnit\Policies\BudgetManagementCostUnitPolicy;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Policies\GeneralSettingsPolicy;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider as ServiceProviderModel;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Policies\ShiftQualificationPolicy;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Invitation::class => InvitationPolicy::class,
        User::class => UserPolicy::class,
        Department::class => DepartmentPolicy::class,
        Project::class => ProjectPolicy::class,
        Checklist::class => ChecklistPolicy::class,
        Sector::class => SectorPolicy::class,
        Category::class => CategoryPolicy::class,
        Genre::class => GenrePolicy::class,
        Comment::class => CommentPolicy::class,
        ChecklistTemplate::class => ChecklistTemplatePolicy::class,
        TaskTemplate::class => TaskTemplatePolicy::class,
        Area::class => AreaPolicy::class,
        Contract::class => ContractPolicy::class,
        Freelancer::class => FreelancerPolicy::class,
        ServiceProviderModel::class => ServiceProviderPolicy::class,
        GeneralSettings::class => GeneralSettingsPolicy::class,
        ShiftQualification::class => ShiftQualificationPolicy::class,
        SageApiSettings::class => SageApiSettingsPolicy::class,
        SageAssignedDataComment::class => SageAssignedDataCommentPolicy::class,
        SageNotAssignedData::class => SageNotAssignedDataPolicy::class,
        BudgetColumnSetting::class => BudgetColumnSettingPolicy::class,
        BudgetManagementAccount::class => BudgetManagementAccountPolicy::class,
        BudgetManagementCostUnit::class => BudgetManagementCostUnitPolicy::class,
        Event::class => EventPolicy::class
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Implicitly grant "admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user) {
            return $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) ? true : null;
        });
    }
}
