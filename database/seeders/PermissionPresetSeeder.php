<?php

namespace Database\Seeders;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Artwork\Modules\Permission\Services\PermissionImplicationService;
use Illuminate\Database\Seeder;

/**
 * Rollenbilder als Rechte-Presets (Konzept Nutzerrechte, Abschnitt 3.4). Idempotent: bestehende
 * Presets gleichen Namens werden nicht überschrieben (Häuser dürfen sie anpassen). Presets speichern
 * Rechte-NAMEN. Die fünf alten Seed-Presets werden entfernt, wenn sie noch dem alten Seed-Stand entsprechen.
 */
class PermissionPresetSeeder extends Seeder
{
    /** @return array<string, PermissionEnum[]> */
    public static function rolePresets(): array
    {
        $basis = [
            PermissionEnum::PROJECT_VIEW,
            PermissionEnum::EVENT_REQUEST,
            PermissionEnum::CAN_VIEW_OWN_ROSTER,
            PermissionEnum::CAN_SUBSCRIBE_SHIFT_CALENDAR,
            PermissionEnum::DAY_REMARKS_VIEW,
            PermissionEnum::CONTRACT_SEE_DOWNLOAD,
        ];

        return [
            'Basis (alle Mitarbeitenden)' => $basis,
            'Produktionsleitung' => [
                ...$basis,
                PermissionEnum::ADD_EDIT_OWN_PROJECT,
                PermissionEnum::PROJECT_MANAGEMENT,
                PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
                PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
                PermissionEnum::DOCUMENT_REQUEST_CREATE,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION,
            ],
            'Disposition / Raumplanung' => [
                ...$basis,
                PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
                PermissionEnum::CAN_EDIT_PLANNING_CALENDAR,
                PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
                PermissionEnum::ROOM_UPDATE,
                PermissionEnum::DAY_REMARKS_EDIT,
                PermissionEnum::EVENT_SETTINGS_UPDATE,
            ],
            'Dienstplanung' => [
                ...$basis,
                PermissionEnum::VIEW_SHIFT_PLAN,
                PermissionEnum::SHIFT_PLANNER,
                PermissionEnum::CAN_COMMIT_SHIFTS,
                PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS,
                PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS,
                PermissionEnum::AVAILABILITY_MANAGEMENT,
                PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT,
            ],
            'Personalverwaltung' => [
                ...$basis,
                PermissionEnum::MA_MANAGER,
                PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO,
                PermissionEnum::CAN_PAY_OUT_OVERTIME,
                PermissionEnum::TEAM_UPDATE,
                PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS,
            ],
            'Buchhaltung / Controlling' => [
                ...$basis,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN,
                PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE,
                PermissionEnum::VIEW_BUDGET_TEMPLATES,
                PermissionEnum::UPDATE_BUDGET_TEMPLATES,
                PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD,
                PermissionEnum::MONEY_SOURCE_EDIT_DELETE,
                PermissionEnum::VIEW_PROJECT_SAGE_DATA,
                PermissionEnum::VIEW_GLOBAL_SAGE_DATA,
                PermissionEnum::BI_DASHBOARD,
                PermissionEnum::BI_EXPORT,
                PermissionEnum::BUDGET_SETTINGS_UPDATE,
                PermissionEnum::MONEY_SOURCE_SETTINGS_UPDATE,
            ],
            'Vertrags- & Dokumentenverwaltung' => [
                ...$basis,
                PermissionEnum::CONTRACT_EDIT_UPLOAD,
                PermissionEnum::DOCUMENT_REQUEST_CREATE,
                PermissionEnum::DOCUMENT_REQUEST_EDIT,
            ],
            'Lager / Inventar' => [
                ...$basis,
                PermissionEnum::INVENTORY_CREATE_EDIT,
                PermissionEnum::INVENTORY_DELETE,
                PermissionEnum::INVENTORY_DISPOSITION,
                PermissionEnum::MATERIAL_ISSUE_LOG_VIEW,
                PermissionEnum::SET_CREATE_EDIT,
                PermissionEnum::SET_DELETE,
                PermissionEnum::INVENTORY_SETTINGS,
            ],
            'Künstler*innenbetreuung / CRM' => [
                ...$basis,
                PermissionEnum::CRM_VIEW,
                PermissionEnum::CRM_MANAGER,
            ],
        ];
    }

    /**
     * Die fünf alten Seed-Presets mit ihrem ursprünglichen Rechteumfang (als Namen). Werden nur
     * entfernt, wenn ihr Inhalt noch exakt diesem Stand entspricht.
     *
     * @return array<string, string[]>
     */
    public static function legacyPresets(): array
    {
        return [
            'Standard User' => [
                PermissionEnum::PROJECT_VIEW->value,
                PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
                PermissionEnum::EVENT_REQUEST->value,
                PermissionEnum::CONTRACT_SEE_DOWNLOAD->value,
            ],
            'Vertrags- & Dokumentenadmin' => [
                PermissionEnum::CONTRACT_EDIT_UPLOAD->value,
                'can see, edit and delete project contracts and docs',
            ],
            'Budgetadmin' => [
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value,
            ],
            'Disponent*in' => [
                PermissionEnum::ROOM_UPDATE->value,
            ],
            'Finanzierungsquellenadmin' => [
                PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
                PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value,
            ],
        ];
    }

    public function run(): void
    {
        $this->removeUnchangedLegacyPresets();

        /** @var PermissionImplicationService $implications */
        $implications = app(PermissionImplicationService::class);

        foreach (self::rolePresets() as $name => $permissions) {
            if (PermissionPreset::query()->where('name', $name)->exists()) {
                continue;
            }

            PermissionPreset::query()->create([
                'name' => $name,
                'permissions' => $implications->expand(
                    array_map(static fn (PermissionEnum $p): string => $p->value, $permissions)
                ),
            ]);
        }
    }

    private function removeUnchangedLegacyPresets(): void
    {
        foreach (self::legacyPresets() as $name => $legacyPermissions) {
            $preset = PermissionPreset::query()->where('name', $name)->first();
            if ($preset === null) {
                continue;
            }

            $current = $preset->permissionNames();
            sort($current);
            // Das tote Recht kann bereits entfernt worden sein und die Stufenleiter-Implikationen können
            // bereits angewendet sein — alle Varianten gelten als "unverändert".
            $legacyWithoutDead = array_values(array_diff($legacyPermissions, ['can see, edit and delete project contracts and docs']));
            $candidates = [
                $legacyPermissions,
                $legacyWithoutDead,
                app(PermissionImplicationService::class)->expand($legacyWithoutDead),
            ];
            foreach ($candidates as $candidate) {
                sort($candidate);
                if ($current === $candidate) {
                    $preset->delete();
                    break;
                }
            }
        }
    }
}
