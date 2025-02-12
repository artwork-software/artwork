<?php

namespace Artwork\Core\Http\Middleware;

use App\Settings\EventSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function share(Request $request): array
    {
        /** @var GeneralSettings $generalSettings */
        $generalSettings = app(GeneralSettings::class);
        $eventSettings = app(EventSettings::class);
        $calendarSettings = Auth::user()?->calendar_settings;

        return array_merge(
            parent::share($request),
            [
                'name' => config('app.name'),
                'small_logo' => $generalSettings->small_logo_path !== "" ?
                    Storage::disk('public')->url($generalSettings->small_logo_path) :
                    null,
                'big_logo' => $generalSettings->big_logo_path !== "" ?
                    Storage::disk('public')->url($generalSettings->big_logo_path) :
                    null,
                'banner' => $generalSettings->banner_path !== "" ?
                    Storage::disk('public')->url($generalSettings->banner_path) :
                    null,
                'projectNameOfCalendarProject' => $calendarSettings?->getAttribute('use_project_time_period') ?
                    $this->projectService->findById(
                        $calendarSettings?->getAttribute('time_period_project_id')
                    )->getAttribute('name') : null,
                'businessName' => $generalSettings->business_name,
                'page_title' => $generalSettings->page_title ?? config('app.name'),
                'impressumLink' => $generalSettings->impressum_link,
                'privacyLink' => $generalSettings->privacy_link,
                'emailFooter' => $generalSettings->email_footer,
                'invitationEmail' => $generalSettings->invitation_email,
                'businessEmail' => $generalSettings->business_email,
                'budgetAccountManagementGlobal' => $generalSettings->budget_account_management_global,
                'show_hints' => Auth::guest() ? false : false,
                'rolesArray' => Auth::guest() ? [] : json_encode(Auth::user()->allRoles(), true),
                'permissionsArray' => Auth::guest() ? [] : json_encode(Auth::user()->allPermissions(), true),
                'myMoneySources' => Auth::guest() ?
                    false :
                    Auth::user()->accessMoneySources()->get(['money_source_id']),
                'urlParameters' => $request->query(),
                'flash' => [
                    'success' => fn() => $request->session()->get('success'),
                    'error' => fn() => $request->session()->get('error'),
                ],
                'event_status_module' => $eventSettings->enable_status,
                'default_language' => config('app.fallback_locale'),
                'selected_language' => Auth::guest() ? app()->getLocale() : Auth::user()->language,
                'sageApiEnabled' => app(SageApiSettingsService::class)->getFirst()?->enabled ?? false,
                'calendar_settings' => $calendarSettings,
                'module_settings' => $this->moduleSettingsService->getModuleSettings(),
                'high_contrast_percent' => $calendarSettings?->getAttribute('high_contrast') ? 75 : 15,
                'isNotionKeySet' => config('app.notion_api_token') !== null && config('app.notion_api_token') !== '',
            ]
        );
    }
}
