<?php

namespace Artwork\Core\Http\Middleware;

use App\Settings\EventSettings;
use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly ProjectService $projectService
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws \JsonException
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function share(Request $request): array
    {
        $generalSettings = app(GeneralSettings::class);
        $generalCalendarSettings = app(GeneralCalendarSettings::class);
        $eventSettings = app(EventSettings::class);

        $user = Auth::user();
        // Settings-Relationen werden hier (vor den Controllern) gelesen und sowohl als Top-Level-Props
        // als auch via auth.user serialisiert. Fehlt der Datensatz (frisch angelegter User / Erstaufruf),
        // einmalig anlegen statt null zu teilen – sonst lesen Kalender-/Schichtplan-Komponenten null und
        // die Seite crasht zum White-Screen.
        $ensureSettings = static function ($user, string $relation) {
            if ($user === null) {
                return null;
            }
            $settings = $user->getAttribute($relation);
            if ($settings === null) {
                $settings = $user->{$relation}()->create();
                $user->setRelation($relation, $settings);
            }
            return $settings;
        };
        $calendarSettings = $ensureSettings($user, 'calendar_settings');
        $dailyViewCalendarSettings = $ensureSettings($user, 'daily_view_calendar_settings');
        $shiftPlanSettings = $ensureSettings($user, 'shift_plan_settings');
        $shiftPlanDailySettings = $ensureSettings($user, 'shift_plan_daily_settings');

        $projectName = null;
        if ($calendarSettings?->use_project_time_period) {
            $projectName = $this->projectService->findById($calendarSettings->time_period_project_id)?->name;
        }

        $storage = Storage::disk('public');
        $smallLogo = $generalSettings->small_logo_path ? $storage->url($generalSettings->small_logo_path) : null;
        $bigLogo = $generalSettings->big_logo_path ? $storage->url($generalSettings->big_logo_path) : null;
        $banner = $generalSettings->banner_path ? $storage->url($generalSettings->banner_path) : null;

        // Rollen/Permissions pro User kurz cachen: diese Queries liefen sonst bei jedem Request
        // (inkl. Permission::all() für Admins) und ein zweites Mal für den 'permissions'-Prop
        // (vormals jsPermissions()). Rollen-Änderungen greifen dadurch mit max. 5 Minuten Verzögerung.
        [$rolesArray, $userPermissions, $permissionsArray] = $user
            ? Cache::remember(
                "user:{$user->id}:roles_permissions",
                now()->addMinutes(5),
                static function () use ($user): array {
                    $roles = $user->allRoles();
                    $userPermissions = $user->allPermissions();
                    $permissions = in_array(RoleEnum::ARTWORK_ADMIN->value, $roles, true)
                        ? Permission::query()->pluck('name')->toArray()
                        : $userPermissions;

                    return [$roles, $userPermissions, $permissions];
                }
            )
            : [[], [], []];

        // erstelle mir ein Array aus $generalCalendarSettings (Start und end ) für stunden z.b. Start: 22:00 end: 08:00 array = [22:00, 23:00, 00:00, 01:00, 02:00, 03:00, 04:00, 05:00, 06:00, 07:00, 08:00]
        $start = explode(':', $generalCalendarSettings->start);
        $end = explode(':', $generalCalendarSettings->end);

        $hours = [];
        $startHour = (int)$start[0];
        $endHour = (int)$end[0];
        $currentHour = $startHour;

        $failSave = 0;
        while (true) {
            $hours[] = str_pad($currentHour, 2, '0', STR_PAD_LEFT) . ':00';
            if ($currentHour === $endHour || $failSave === 24) {
                break;
            }
            $currentHour = ($currentHour + 1) % 24;
            $failSave++;
        }
        $sageApiEnabled = false;

        if (env('SAGE_API_ENABLED', false)) {
            $sageApiSettingsService = app(SageApiSettingsService::class);
            $sageApiSettings = $sageApiSettingsService->getFirst();
            $sageApiEnabled = !is_null($sageApiSettings) && $sageApiSettings->enabled;
        }

        $shiftCommitWorkflowEnabled = (bool) $generalSettings->shift_commit_workflow_enabled;

        $isUserWorkFlowUser = false;
        $hasCraftPlanningRights = false;
        $isGlobalShiftPlanner = false;

        if ($user) {
            // Cache-Key pro User
            $cacheKey = "user:{$user->id}:shift_workflow_flags";

            [
                $isUserWorkFlowUser,
                $hasCraftPlanningRights,
                $isGlobalShiftPlanner,
            ] = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
                $isWorkflowUser = ShiftCommitWorkflowUser::where('user_id', $user->id)->exists();
                $hasCraftPlanning = Craft::whereHas('craftShiftPlaner', function ($q) use ($user): void {
                    $q->where('user_id', $user->id);
                })->exists();
                $isGlobalPlanner =
                    $user->can('can view shift plan')
                    || $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);

                return [
                    $isWorkflowUser,
                    $hasCraftPlanning,
                    $isGlobalPlanner,
                ];
            });
        }

        $canSeeShiftPlanReview = $shiftCommitWorkflowEnabled && (
                $isUserWorkFlowUser
                || ($user && $user->hasRole(RoleEnum::ARTWORK_ADMIN->value))
            );

        $canSeeShiftPlanChangeList = $canSeeShiftPlanReview;

        $canSeeShiftPlanRequestedPlans = $shiftCommitWorkflowEnabled && (
                $isGlobalShiftPlanner
                || $hasCraftPlanningRights
                || ($user && $user->hasRole(RoleEnum::ARTWORK_ADMIN->value))
            );

        // Drei exists()-Queries pro Request vermeiden — Ergebnis ändert sich selten, 5 Minuten cachen
        $canSeeIncomingRequests = $user
            ? Cache::remember(
                "user:{$user->id}:can_see_incoming_requests",
                now()->addMinutes(5),
                static fn (): bool => $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
                    || $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value)
                    || DB::table('room_user')->where('user_id', $user->id)->where('is_admin', true)->exists()
                    || DB::table('event_type_user')->where('user_id', $user->id)->exists()
                    || DB::table('event_types')->where('specific_verifier_id', $user->id)->exists()
            )
            : false;

        $canSeeEventVerifications = (bool) $user;

        $canViewBiDashboard = $user && (
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->can(PermissionEnum::BI_DASHBOARD->value)
        );

        // Tagesbemerkungen: Instanz-Setting + Berechtigungen als ein kompakter Prop,
        // damit Kalender, Planungskalender, Dienstplan und Anzeigeeinstellungen
        // dieselbe Sichtbarkeitslogik nutzen ($permissionsArray enthält für Admins
        // bereits alle Permission-Namen).
        $canEditDayRemarks = in_array(PermissionEnum::DAY_REMARKS_EDIT->value, $permissionsArray, true);
        $dayRemarksState = [
            'enabled' => (bool) $generalCalendarSettings->day_remarks_enabled,
            'mandatory' => (bool) $generalCalendarSettings->day_remarks_mandatory,
            'can_edit' => $canEditDayRemarks,
            'can_view' => $canEditDayRemarks
                || in_array(PermissionEnum::DAY_REMARKS_VIEW->value, $permissionsArray, true),
        ];

        return array_merge(
            parent::share($request),
            [
                'name' => config('app.name'),
                'use_chat_module' => config('app.use_chat_module'),
                'small_logo' => $smallLogo,
                'big_logo' => $bigLogo,
                'banner' => $banner,
                'projectNameOfCalendarProject' => $projectName,
                'businessName' => $generalSettings->business_name,
                'event_time_length_minutes' => $generalSettings->event_time_length_minutes,
                'event_start_time' => $generalSettings->event_start_time,
                'event_all_day_default' => $generalSettings->event_all_day_default,
                'warn_multiple_assignments' => $generalSettings->warn_multiple_assignments,
                'page_title' => $generalSettings->page_title ?? config('app.name'),
                'impressumLink' => $generalSettings->impressum_link,
                'privacyLink' => $generalSettings->privacy_link,
                'emailFooter' => $generalSettings->email_footer,
                'invitationEmail' => $generalSettings->invitation_email,
                'businessEmail' => $generalSettings->business_email,
                'playingTimeWindowStart' => $generalSettings->playing_time_window_start,
                'playingTimeWindowEnd' => $generalSettings->playing_time_window_end,
                'letterheadName' => $generalSettings->letterhead_name,
                'letterheadStreet' => $generalSettings->letterhead_street,
                'letterheadZipCode' => $generalSettings->letterhead_zip_code,
                'letterheadCity' => $generalSettings->letterhead_city,
                'letterheadEmail' => $generalSettings->letterhead_email,
                'budgetAccountManagementGlobal' => $generalSettings->budget_account_management_global,
                'inventoryDetailedArticlesAlwaysQuantityOne' => $generalSettings->inventory_detailed_articles_always_quantity_one,
                'inventoryShowInventoryNumberAsName' => $generalSettings->inventory_show_inventory_number_as_name,
                'inventoryNumberPrefix' => $generalSettings->inventory_number_prefix,
                'inventoryArticleImageMaxSizeMb' => $generalSettings->inventory_article_image_max_size_mb,
                'show_hints' => Auth::guest() ? false : false,
                'rolesArray' => $rolesArray,
                'permissionsArray' => $permissionsArray,
                'myMoneySources' => $user ? $user->accessMoneySources()->pluck('money_source_id') : [],
                'urlParameters' => $request->query(),
                'flash' => [
                    'success' => fn() => $request->session()->get('success'),
                    'error' => fn() => $request->session()->get('error'),
                ],
                'event_status_module' => $eventSettings->enable_status,
                'default_language' => config('app.fallback_locale'),
                'selected_language' => app()->getLocale(),
                'sageApiEnabled' => $sageApiEnabled,
                'calendar_settings' => $calendarSettings,
                'daily_view_calendar_settings' => $dailyViewCalendarSettings,
                'shift_plan_settings' => $shiftPlanSettings,
                'shift_plan_daily_settings' => $shiftPlanDailySettings,
                'module_settings' => $this->moduleSettingsService->getModuleSettings(),
                'isNotionKeySet' => config('app.notion_api_token') !== null && config('app.notion_api_token') !== '',
                'calendarHours' => $hours,
                // Gleiche Struktur wie vormals jsPermissions() ({roles, permissions}), aber ohne die
                // doppelten Rollen-/Permission-Queries und den json_encode/json_decode-Roundtrip
                'permissions' => $user
                    ? ['roles' => $rolesArray, 'permissions' => $userPermissions]
                    : [],
                // chatUsers only on reload and not on page change
                'chats' => Inertia::lazy(fn() => $user?->chats()->with(['users'])->get()),
                'shiftCommitWorkflow'          => $shiftCommitWorkflowEnabled,
                'allow_shift_overbooking'      => (bool) app(\App\Settings\ShiftSettings::class)
                    ->allow_shift_overbooking,
                'isUserWorkFlowUser'           => $isUserWorkFlowUser,
                'canSeeShiftPlanReview'        => $canSeeShiftPlanReview,
                'canSeeShiftPlanChangeList'    => $canSeeShiftPlanChangeList,
                'canSeeShiftPlanRequestedPlans' => $canSeeShiftPlanRequestedPlans,
                'canSeeEventVerifications'      => $canSeeEventVerifications,
                'canSeeIncomingRequests'         => $canSeeIncomingRequests,
                'canViewBiDashboard'             => $canViewBiDashboard,
                'day_remarks'                    => $dayRemarksState,
            ]
        );
    }
}
