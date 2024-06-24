<?php

namespace Artwork\Core\Http\Middleware;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function share(Request $request): array
    {
        /** @var GeneralSettings $generalSettings */
        $generalSettings = app(GeneralSettings::class);
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
                'businessName' => $generalSettings->business_name,
                'page_title' => $generalSettings->page_title ?? config('app.name'),
                'impressumLink' => $generalSettings->impressum_link,
                'privacyLink' => $generalSettings->privacy_link,
                'emailFooter' => $generalSettings->email_footer,
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
                'default_language' => config('app.fallback_locale'),
                'selected_language' => Auth::guest() ? app()->getLocale() : Auth::user()->language,
                'sageApiEnabled' => app(SageApiSettingsService::class)->getFirst()?->enabled ?? false,
                'calendar_settings' => $calendarSettings,
            ]
        );
    }
}
