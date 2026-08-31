<?php

namespace App\Providers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Policies\AreaPolicy;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Policies\SageAssignedDataCommentPolicy;
use Artwork\Modules\Budget\Policies\SageNotAssignedDataPolicy;
use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Budget\Policies\BudgetColumnSettingPolicy;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Policies\BudgetManagementAccountPolicy;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Policies\BudgetManagementCostUnitPolicy;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Category\Policies\CategoryPolicy;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Policies\ChecklistPolicy;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Checklist\Policies\ChecklistTemplatePolicy;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Policies\ContractPolicy;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Policies\DepartmentPolicy;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Policies\EventPolicy;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Policies\ExternalAccessPolicy;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Policies\FreelancerPolicy;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Policies\GeneralSettingsPolicy;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Genre\Policies\GenrePolicy;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Invitation\Policies\InvitationPolicy;
use Artwork\Modules\ModuleSettings\Http\Policies\ModuleSettingsPolicy;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Policies\CommentPolicy;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Policies\SageApiSettingsPolicy;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Sector\Policies\SectorPolicy;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider as ServiceProviderModel;
use Artwork\Modules\ServiceProvider\Policies\ServiceProviderPolicy;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Policies\ShiftQualificationPolicy;
use Artwork\Modules\System\ApiManagement\Policies\TokenPolicy;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\TaskTemplate\Policies\TaskTemplatePolicy;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Policies\UserPolicy;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Artwork\Modules\Webhook\Policies\WebhookEndpointPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;

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
        Token::class => TokenPolicy::class,
        WebhookEndpoint::class => WebhookEndpointPolicy::class,
        ShiftQualification::class => ShiftQualificationPolicy::class,
        SageApiSettings::class => SageApiSettingsPolicy::class,
        SageAssignedDataComment::class => SageAssignedDataCommentPolicy::class,
        SageNotAssignedData::class => SageNotAssignedDataPolicy::class,
        BudgetColumnSetting::class => BudgetColumnSettingPolicy::class,
        BudgetManagementAccount::class => BudgetManagementAccountPolicy::class,
        BudgetManagementCostUnit::class => BudgetManagementCostUnitPolicy::class,
        Event::class => EventPolicy::class,
        ModuleSettings::class => ModuleSettingsPolicy::class,
        ExternalAccess::class => ExternalAccessPolicy::class,
        \Artwork\Modules\Chat\Models\Chat::class => \Artwork\Modules\Chat\Policies\ChatPolicy::class,
        \Artwork\Modules\Vacation\Models\Vacation::class =>
            \Artwork\Modules\Vacation\Policies\VacationPolicy::class,
        \Artwork\Modules\IndividualTimes\Models\IndividualTime::class =>
            \Artwork\Modules\IndividualTimes\Policies\IndividualTimePolicy::class,
        \Artwork\Modules\Availability\Models\Availability::class =>
            \Artwork\Modules\Availability\Policies\AvailabilityPolicy::class,
        \Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest::class =>
            \Artwork\Modules\WorkTime\Policies\WorkTimeChangeRequestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Passport::$clientUuids = false;

        // Scopes der Maschinen-API. Ein Scope, der hier fehlt, lässt sich nicht vergeben — die
        // ScopeRepository lehnt ihn beim Anlegen mit invalid_scope ab.
        //
        // Wichtig: Scopes stehen im signierten JWT, nicht in der Datenbank. Tokens, die vor der
        // Einführung dieser Liste ausgegeben wurden, tragen dauerhaft eine leere Scope-Menge und
        // können nachträglich keine Rechte erhalten — sie müssen neu erstellt werden.
        Passport::tokensCan([
            'inventory:read' => 'Read inventory categories and articles',
            'ticketing:read' => 'Read released events, price categories and branding',
            'ticketing:write' => 'Report sales, bookings and check-ins back to artwork',
        ]);

        // Passport ≥12 registriert keine Default-View mehr – ohne dieses Binding
        // wirft /oauth/authorize eine BindingResolutionException, sobald ein
        // Client eine Consent-Abfrage braucht.
        Passport::authorizationView('oauth.authorize');

        // Implicitly grant "admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user) {
            // Type-check so that ExternalAccess (or any non-User identity) does not slip into
            // the admin bypass — external identities run normally through policies.
            if (!$user instanceof User) {
                return null;
            }
            return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value) ? true : null;
        });

        // Genehmiger-Seite des Dienstplan-Festschreibungs-Workflows: wer als
        // ShiftCommitWorkflowUser hinterlegt ist, darf Anfragen prüfen, genehmigen,
        // ablehnen und Änderungen nach Festschreibung bearbeiten (Admins via Gate::before).
        Gate::define('approve-shift-plan-requests', function (User $user): bool {
            return ShiftCommitWorkflowUser::where('user_id', $user->id)->exists();
        });
    }
}
