<?php

namespace Artwork\Modules\Permission\Services;

use App\Settings\GeneralCalendarSettings;
use App\Settings\ShiftSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Liefert den Rechte-Katalog plus Instanz- und Personenzustand an die Rechteseite, das Presets-Modal,
 * das Einladungsmodal und die Referenzseite. Texte bleiben Übersetzungsschlüssel ($t im Frontend).
 */
readonly class PermissionCatalogPresenter
{
    public function __construct(
        private PermissionCatalog $catalog,
        private ModuleSettingsService $moduleSettingsService,
    ) {
    }

    /**
     * @return array{modules: array<int, array<string, mixed>>, instance: array<string, mixed>, user: array<string, mixed>|null}
     */
    public function present(?User $user = null): array
    {
        return [
            'modules' => $this->catalog->toArray()->all(),
            'instance' => $this->instanceState(),
            'usage' => $this->usageCounts(),
            'user' => $user === null ? null : [
                'id' => $user->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'is_admin' => $user->hasRole(RoleEnum::ARTWORK_ADMIN->value),
            ],
        ];
    }

    /**
     * Wie viele Personen ein Recht direkt besitzen (ohne Admin-Bypass) — für "Aktuell bei n Personen vergeben".
     *
     * @return array<string, int>
     */
    public function usageCounts(): array
    {
        return DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.model_type', (new User())->getMorphClass())
            ->groupBy('permissions.name')
            ->selectRaw('permissions.name, COUNT(*) as count')
            ->pluck('count', 'name')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * Modul-Schalter, relevante Einstellungen und Feature-Flags, gegen die Requirement-Chips geprüft werden.
     *
     * @return array{modules: array<string, bool>, settings: array<string, bool>, features: array<string, bool>}
     */
    public function instanceState(): array
    {
        $moduleSettings = $this->moduleSettingsService->getModuleSettings();
        $modules = [];
        foreach ($moduleSettings->toArray() as $key => $enabled) {
            $modules[$key] = (bool) $enabled;
        }

        $shiftSettings = app(ShiftSettings::class);
        $calendarSettings = app(GeneralCalendarSettings::class);
        $generalSettings = app(GeneralSettings::class);

        $sageEnabled = false;
        if (config('services.sage.enabled')) {
            $sageApiSettings = app(SageApiSettingsService::class)->getFirst();
            $sageEnabled = $sageApiSettings !== null && (bool) $sageApiSettings->enabled;
        }

        return [
            'modules' => $modules,
            'settings' => [
                'shift_granular_permissions' => (bool) ($shiftSettings->granular_permissions_enabled ?? false),
                'hide_uncommitted_shifts' => (bool) ($shiftSettings->hide_uncommitted_shifts_from_own_roster ?? false),
                'day_remarks_enabled' => (bool) ($calendarSettings->day_remarks_enabled ?? false),
                'shift_commit_workflow' => (bool) ($generalSettings->shift_commit_workflow_enabled ?? false),
            ],
            'features' => [
                'sage_api' => $sageEnabled,
                // Externe einladen: UI bewusst deaktiviert (CRM/Index.vue, TabContent.vue)
                'external_access' => false,
            ],
        ];
    }
}
