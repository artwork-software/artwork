<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleExternalInertiaRequests extends Middleware
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
    ) {
    }

    public function rootView(Request $request): string
    {
        return 'app-external';
    }

    /**
     * Deliberately narrow share — must never leak internal user permissions/roles/settings.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var ExternalAccess|null $external */
        $external = $request->user('external');

        $storage = Storage::disk('public');
        $smallLogo = $this->generalSettings->small_logo_path
            ? $storage->url($this->generalSettings->small_logo_path)
            : null;
        $bigLogo = $this->generalSettings->big_logo_path
            ? $storage->url($this->generalSettings->big_logo_path)
            : null;
        $banner = $this->generalSettings->banner_path
            ? $storage->url($this->generalSettings->banner_path)
            : null;

        return array_merge(parent::share($request), [
            'auth' => [
                'external' => $external !== null ? $this->shareExternal($external) : null,
            ],
            'accessible_scopes' => fn () => $external !== null
                ? $this->resolveAccessibleScopes($external)
                : [],
            'crm_access_expires_at' => $external?->crm_access_expires_at?->toIso8601String(),
            'page_title' => $this->generalSettings->page_title ?: config('app.name'),
            'small_logo' => $smallLogo,
            'big_logo' => $bigLogo,
            'banner' => $banner,
            'company_name' => $this->generalSettings->business_name ?? null,
            'flash' => fn () => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'impressumLink' => $this->generalSettings->impressum_link ?? null,
            'privacyLink' => $this->generalSettings->privacy_link ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function shareExternal(ExternalAccess $external): array
    {
        return [
            'id' => $external->id,
            'crm_contact_id' => $external->crm_contact_id,
            'display_name' => $external->crmContact?->display_name,
            'email' => $external->email,
            'crm_access_expires_at' => $external->crm_access_expires_at?->toIso8601String(),
        ];
    }

    /**
     * Liefert die aktuell gueltigen Scopes als minimale Liste fuer die Mainnav.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveAccessibleScopes(ExternalAccess $external): array
    {
        return $external->scopes()
            ->currentlyValid()
            ->with(['project', 'projectTab'])
            ->get()
            ->filter(fn (ExternalAccessScope $scope) => $scope->project && $scope->projectTab)
            ->map(fn (ExternalAccessScope $scope) => [
                'id' => $scope->id,
                'project' => [
                    'id' => $scope->project->id,
                    'name' => $scope->project->name,
                ],
                'tab' => [
                    'id' => $scope->projectTab->id,
                    'name' => $scope->projectTab->name,
                ],
                'access_type' => $scope->access_type->value,
                'valid_to' => $scope->valid_to->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
