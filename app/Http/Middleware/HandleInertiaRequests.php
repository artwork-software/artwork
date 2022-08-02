<?php

namespace App\Http\Middleware;

use App\Models\GeneralSettings;
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

        return array_merge(parent::share($request), [
            'roles' => Auth::guest() ? [] : Auth::user()->getRoleNames(),
            'permissions' => Auth::guest() ? [] : Auth::user()->getPermissionNames(),
            'can' => [
                'view_users' => Auth::guest() ? false : Auth::user()->can("view users"),
                'view_departments' => Auth::guest() ? false : Auth::user()->can("view departments"),
                'show_hints' => Auth::guest() ? false : Auth::user()->toggle_hints,
            ],
            'is_admin' => Auth::guest() ? false : Auth::user()->hasRole('admin'),
            'small_logo' => $this->small_logo(),
            'big_logo' => $this->big_logo(),
            'banner' => $this->banner(),
            'teams' => Auth::user()->departments,
            'impressumLink' => app(GeneralSettings::class)->impressum_link,
            'privacyLink' => app(GeneralSettings::class)->privacy_link,
            'emailFooter' => app(GeneralSettings::class)->email_footer
        ]);
    }
}
