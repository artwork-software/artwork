<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\GeneralSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function banner(): ?string
    {
        $path = app(GeneralSettings::class)->banner_path;

        if ($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }
    }

    public function smallLogo(): ?string
    {
        $path = app(GeneralSettings::class)->small_logo_path;

        if ($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }
    }

    public function bigLogo(): ?string
    {
        $path = app(GeneralSettings::class)->big_logo_path;

        if ($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(
            parent::share($request),
            array_merge(
                Auth::guest() ?
                    $this->getGuestPermissionsAndRoles() :
                    $this->getUserPermissionsAndRoles(Auth::user()),
                [
                    'small_logo' => $this->smallLogo(),
                    'big_logo' => $this->bigLogo(),
                    'banner' => $this->banner(),
                    'businessName' => app(GeneralSettings::class)->business_name,
                    'impressumLink' => app(GeneralSettings::class)->impressum_link,
                    'privacyLink' => app(GeneralSettings::class)->privacy_link,
                    'emailFooter' => app(GeneralSettings::class)->email_footer,
                    'myMoneySources' => Auth::guest() ?
                        false :
                        Auth::user()->accessMoneySources()->get(['money_source_id']),
                    'urlParameters' => $request->query(),
                    'flash' => [
                        'success' => fn() => $request->session()->get('success'),
                        'error' => fn() => $request->session()->get('error')
                    ]
                ]
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getGuestPermissionsAndRoles(): array
    {
        return [
            'rolesArray' => [],
            'permissionsArray' => [],
            'can' => [
                'show_hints' => false,
                'view_projects' => false,
                'project_management' => false,
                'own_projects' => false,
                'edit_projects' => false,
                'delete_projects' => false,
                'request_room' => false,
                'read_room_request_details' => false,
                'edit_rooms' => false,
                'contract_upload_edit' => false,
                'money_source_edit_add' => false,
                'edit_settings' => false,
                'add_user' => false,
                'edit_teams' => false,
                'edit_project_settings' => false,
                'edit_event_settings' => false,
                'edit_checklist_settings' => false,
                'global_notification' => false,
                'edit_budget_templates' => false,
                'view_budget_templates' => false,
                'shift_planner' => false,
                'ma_manager' => false,
                'view_shift_plan' => false,
                'can_commit_shifts' => false,
                'global_project_budget_admin' => false,
                'edit_external_users_conditions' => false,
            ],
            'is_admin' => false,
            'is_budget_admin' => false,
            'is_contract_admin' => false,
            'is_money_source_admin' => false,
            'is_room_admin' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserPermissionsAndRoles(Authenticatable $user): array
    {
        return [
            'rolesArray' => json_encode($user->allRoles, true),
            'permissionsArray' => json_encode($user->allPermissions, true),
            'can' => [
                'show_hints' => $user->toggle_hints,
                'view_projects' => $user->can(PermissionNameEnum::PROJECT_VIEW->value),
                'project_management' => $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value),
                'own_projects' => $user->can(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value),
                'edit_projects' => $user->can(PermissionNameEnum::WRITE_PROJECTS->value),
                'delete_projects' => $user->can(PermissionNameEnum::PROJECT_DELETE->value),
                'request_room' => $user->can(PermissionNameEnum::EVENT_REQUEST->value),
                'read_room_request_details' => $user->can(PermissionNameEnum::ROOM_REQUEST_READING_DETAILS->value),
                'edit_rooms' => $user->can(PermissionNameEnum::ROOM_UPDATE->value),
                'contract_upload_edit' => $user->can(PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value),
                'money_source_edit_add' => $user->can(PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value),
                'edit_settings' => $user->can(PermissionNameEnum::SETTINGS_UPDATE->value),
                'add_user' => $user->can(PermissionNameEnum::USER_UPDATE->value),
                'edit_teams' => $user->can(PermissionNameEnum::TEAM_UPDATE->value),
                'edit_project_settings' => $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value),
                'edit_event_settings' => $user->can(PermissionNameEnum::EVENT_SETTINGS_UPDATE->value),
                'edit_checklist_settings' => $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value),
                'global_notification' => $user->can(PermissionNameEnum::SYSTEM_NOTIFICATION->value),
                'edit_budget_templates' => $user->can(PermissionNameEnum::UPDATE_BUDGET_TEMPLATES->value),
                'view_budget_templates' => $user->can(PermissionNameEnum::VIEW_BUDGET_TEMPLATES->value),
                'shift_planner' => $user->can(PermissionNameEnum::SHIFT_PLANNER->value),
                'ma_manager' => $user->can(PermissionNameEnum::MA_MANAGER->value),
                'view_shift_plan' => $user->can(PermissionNameEnum::VIEW_SHIFT_PLAN->value),
                'can_commit_shifts' => $user->can(PermissionNameEnum::CAN_COMMIT_SHIFTS->value),
                'global_project_budget_admin' => $user->can(PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value),
                'edit_external_users_conditions' => $user->can(
                    PermissionNameEnum::EDIT_EXTERNAL_USERS_CONDITIONS->value
                ),
            ],
            'is_admin' => $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value),
            'is_budget_admin' => $user->hasRole(RoleNameEnum::BUDGET_ADMIN->value),
            'is_contract_admin' => $user->hasRole(RoleNameEnum::CONTRACT_ADMIN->value),
            'is_money_source_admin' => $user->hasRole(RoleNameEnum::MONEY_SOURCE_ADMIN->value),
            'is_room_admin' => $user->hasRole(RoleNameEnum::ROOM_ADMIN->value),
        ];
    }
}
