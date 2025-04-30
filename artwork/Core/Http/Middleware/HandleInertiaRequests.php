<?php

namespace Artwork\Core\Http\Middleware;

use App\Settings\EventSettings;
use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $calendarSettings = $user?->calendar_settings;

        $projectName = null;
        if ($calendarSettings?->use_project_time_period) {
            $projectName = $this->projectService->findById($calendarSettings->time_period_project_id)?->name;
        }

        $storage = Storage::disk('public');
        $smallLogo = $generalSettings->small_logo_path ? $storage->url($generalSettings->small_logo_path) : null;
        $bigLogo = $generalSettings->big_logo_path ? $storage->url($generalSettings->big_logo_path) : null;
        $banner = $generalSettings->banner_path ? $storage->url($generalSettings->banner_path) : null;

        $rolesArray = $user ? $user->allRoles() : [];
        $permissionsArray = $user ?  $user->hasRole([RoleEnum::ARTWORK_ADMIN->value]) ? Permission::all()->pluck('name') : $user->allPermissions() : [];

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
                'page_title' => $generalSettings->page_title ?? config('app.name'),
                'impressumLink' => $generalSettings->impressum_link,
                'privacyLink' => $generalSettings->privacy_link,
                'emailFooter' => $generalSettings->email_footer,
                'invitationEmail' => $generalSettings->invitation_email,
                'businessEmail' => $generalSettings->business_email,
                'budgetAccountManagementGlobal' => $generalSettings->budget_account_management_global,
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
                'selected_language' => Auth::guest() ? app()->getLocale() : $user->language,
                'sageApiEnabled' => $sageApiEnabled,
                'calendar_settings' => $calendarSettings,
                'module_settings' => $this->moduleSettingsService->getModuleSettings(),
                'high_contrast_percent' => $calendarSettings?->getAttribute('high_contrast') ? 75 : 15,
                'isNotionKeySet' => config('app.notion_api_token') !== null && config('app.notion_api_token') !== '',
                'calendarHours' => $hours,
                'permissions' => json_decode(auth()->check() ? auth()->user()->jsPermissions() : '{}', true, 512, JSON_THROW_ON_ERROR),
                // chatUsers only on reload and not on page change
                'chats' => Inertia::lazy(fn() => $user?->chats()->with(['users'])->get()),
            ]
        );
    }
}
