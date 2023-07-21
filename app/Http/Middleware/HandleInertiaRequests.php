<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\GeneralSettings;
use App\Models\GlobalNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;
use Spatie\Permission\Models\Role;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function banner(): ?string
    {
        $path = app(GeneralSettings::class)->banner_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }

    public function small_logo(): ?string
    {
        $path = app(GeneralSettings::class)->small_logo_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }

    public function big_logo(): ?string
    {
        $path = app(GeneralSettings::class)->big_logo_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        $globalNotification = GlobalNotification::first();
        $globalNotification['image_url'] = $globalNotification?->image_name ? Storage::disk('public')->url($globalNotification->image_name) : null;

        return array_merge(parent::share($request), [
            'rolesArray' => Auth::guest() ? [] : json_encode(Auth::user()->allRoles, true),
            'permissionsArray' => Auth::guest() ? [] : json_encode(Auth::user()->allPermissions, true),
            'can' => [
                'show_hints' => Auth::guest() ? false : Auth::user()->toggle_hints,
                // Projects
                'view_projects' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::PROJECT_VIEW->value),
                'project_management' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::PROJECT_MANAGEMENT->value),
                'own_projects' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value),
                'edit_projects' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::WRITE_PROJECTS->value),
                'delete_projects' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::PROJECT_DELETE->value),

                // ROOM
                'request_room' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::EVENT_REQUEST->value),
                'read_room_request_details' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::ROOM_REQUEST_READING_DETAILS->value),
                'edit_rooms'=> Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::ROOM_UPDATE->value),

                // Docs & Budget
                'contract_upload_edit' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value),
                'money_source_edit_add' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value),

                // Systems
                'edit_settings' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::SETTINGS_UPDATE->value),
                'add_user' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::USER_UPDATE->value),
                'edit_teams' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::TEAM_UPDATE->value),
                'edit_project_settings' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value),
                'edit_event_settings' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::EVENT_SETTINGS_UPDATE->value),
                'edit_checklist_settings' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value),
                'global_nofitication' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::SYSTEM_NOTIFICATION->value),

                // Shifts
                'shift_planner' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::SHIFT_PLANNER->value),
                'ma_manager' => Auth::guest() ? false : Auth::user()->can(PermissionNameEnum::MA_MANAGER->value),


            ],
            'is_admin' => Auth::guest() ? false : Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value),
            'is_budget_admin' => Auth::guest() ? false : Auth::user()->hasRole(RoleNameEnum::BUDGET_ADMIN->value),
            'is_contract_admin' => Auth::guest() ? false : Auth::user()->hasRole(RoleNameEnum::CONTRACT_ADMIN->value),
            'is_money_source_admin' => Auth::guest() ? false : Auth::user()->hasRole(RoleNameEnum::MONEY_SOURCE_ADMIN->value),
            'is_room_admin' => Auth::guest() ? false : Auth::user()->hasRole(RoleNameEnum::ROOM_ADMIN->value),

            'small_logo' => $this->small_logo(),
            'big_logo' => $this->big_logo(),
            'banner' => $this->banner(),
            'impressumLink' => app(GeneralSettings::class)->impressum_link,
            'privacyLink' => app(GeneralSettings::class)->privacy_link,
            'emailFooter' => app(GeneralSettings::class)->email_footer,
            'globalNotification' => $globalNotification,
            'myMoneySources' => Auth::guest() ? false : Auth::user()->accessMoneySources()->get(['money_source_id'])

        ]);
    }
}
