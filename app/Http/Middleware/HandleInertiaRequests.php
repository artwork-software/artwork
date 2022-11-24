<?php

namespace App\Http\Middleware;

use App\Models\GeneralSettings;
use App\Models\GlobalNotification;
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
        $globalNotification['image_url'] = Storage::disk('public')->url($globalNotification->image_name);

        return array_merge(parent::share($request), [
            'roles' => Auth::guest() ? [] : Auth::user()->getRoleNames(),
            'permissions' => Auth::guest() ? [] : Auth::user()->getPermissionNames(),
            'can' => [
                'view_projects' => Auth::guest() ? false : Auth::user()->can("view projects"),
                'create_and_edit_projects' => Auth::guest() ? false : Auth::user()->can("create and edit projects"),
                'admin_projects' => Auth::guest() ? false : Auth::user()->can("admin projects"),
                'delete_projects' => Auth::guest() ? false : Auth::user()->can("delete projects"),
                'change_tool_settings' => Auth::guest() ? false : Auth::user()->can("change tool settings"),
                'usermanagement' => Auth::guest() ? false : Auth::user()->can("usermanagement"),
                'teammanagement' => Auth::guest() ? false : Auth::user()->can("teammanagement"),
                'admin_projectSettings' => Auth::guest() ? false : Auth::user()->can("admin projectSettings"),
                'admin_eventTypeSettings' => Auth::guest() ? false : Auth::user()->can("admin eventTypeSettings"),
                'admin_checklistTemplates' => Auth::guest() ? false : Auth::user()->can("admin checklistTemplates"),
                'admin_rooms' => Auth::guest() ? false : Auth::user()->can("admin rooms"),
                'request_room_occupancy' => Auth::guest() ? false : Auth::user()->can("request room occupancy"),
                'view_occupancy_requests' => Auth::guest() ? false : Auth::user()->can("view occupancy requests"),
                'show_hints' => Auth::guest() ? false : Auth::user()->toggle_hints,
            ],
            'is_admin' => Auth::guest() ? false : Auth::user()->hasRole('admin'),
            'small_logo' => $this->small_logo(),
            'big_logo' => $this->big_logo(),
            'banner' => $this->banner(),
            'impressumLink' => app(GeneralSettings::class)->impressum_link,
            'privacyLink' => app(GeneralSettings::class)->privacy_link,
            'emailFooter' => app(GeneralSettings::class)->email_footer,
            'globalNotification' => $globalNotification,
        ]);
    }
}
