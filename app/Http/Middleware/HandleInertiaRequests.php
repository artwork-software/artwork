<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\GeneralSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $generalSettings = app(GeneralSettings::class);
        return array_merge(
            parent::share($request),
            [
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
                'impressumLink' => $generalSettings->impressum_link,
                'privacyLink' => $generalSettings->privacy_link,
                'emailFooter' => $generalSettings->email_footer,
                'businessEmail' => $generalSettings->business_email,
                'show_hints' => Auth::guest() ? false : Auth::user()->toggle_hints,
                'rolesArray' => Auth::guest() ? [] : json_encode(Auth::user()->allRoles, true),
                'permissionsArray' => Auth::guest() ? [] : json_encode(Auth::user()->allPermissions, true),
                'myMoneySources' => Auth::guest() ?
                    false :
                    Auth::user()->accessMoneySources()->get(['money_source_id']),
                'urlParameters' => $request->query(),
                'flash' => [
                    'success' => fn() => $request->session()->get('success'),
                    'error' => fn() => $request->session()->get('error')
                ],
                'sageApiEnabled' => app(SageApiSettingsService::class)->getFirst()?->enabled ?? false
            ]
        );
    }
}
