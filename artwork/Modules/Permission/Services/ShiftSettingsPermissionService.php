<?php

namespace Artwork\Modules\Permission\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Models\PermissionPreset;

class ShiftSettingsPermissionService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $areas = [
            [PermissionEnum::SHIFT_SETTINGS_GENERAL_VIEW, 'Allgemeine Schichteinstellungen ansehen', 'View general shift settings', 'Darf Gewerke, Funktionen, Qualifikationen und allgemeine Schichteinstellungen ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT, 'Allgemeine Schichteinstellungen bearbeiten', 'Edit general shift settings', 'Darf Gewerke, Funktionen, Qualifikationen und allgemeine Schichteinstellungen bearbeiten.'],
            [PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_VIEW, 'Tagesdienste ansehen', 'View day services', 'Darf Tagesdienste in den Schichteinstellungen ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_EDIT, 'Tagesdienste bearbeiten', 'Edit day services', 'Darf Tagesdienste anlegen, bearbeiten und löschen.'],
            [PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_VIEW, 'Arbeitszeitmuster ansehen', 'View work time patterns', 'Darf Arbeitszeitmuster ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_EDIT, 'Arbeitszeitmuster bearbeiten', 'Edit work time patterns', 'Darf Arbeitszeitmuster anlegen, bearbeiten und löschen.'],
            [PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_VIEW, 'Nutzerverträge ansehen', 'View user contracts', 'Darf Vertragsvorlagen und deren Einstellungen ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_EDIT, 'Nutzerverträge bearbeiten', 'Edit user contracts', 'Darf Vertragsvorlagen anlegen, bearbeiten und löschen.'],
            [PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_VIEW, 'Schichtgruppen ansehen', 'View shift groups', 'Darf Schichtgruppen ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_EDIT, 'Schichtgruppen bearbeiten', 'Edit shift groups', 'Darf Schichtgruppen anlegen, bearbeiten und löschen.'],
            [PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW, 'Schichtvorlagen ansehen', 'View shift templates', 'Darf Schichtvorlagen und Vorlagengruppen ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_EDIT, 'Schichtvorlagen bearbeiten', 'Edit shift templates', 'Darf Schichtvorlagen und Vorlagengruppen anlegen, bearbeiten und löschen.'],
            [PermissionEnum::SHIFT_SETTINGS_RULES_VIEW, 'Schichtregeln ansehen', 'View shift rules', 'Darf Schichtregeln, Warnungen und offene Verstöße ansehen.'],
            [PermissionEnum::SHIFT_SETTINGS_RULES_EDIT, 'Schichtregeln bearbeiten', 'Edit shift rules', 'Darf Schichtregeln und Verstöße bearbeiten.'],
        ];

        $definitions = array_map(static fn (array $area): array => [
            'name' => $area[0]->value,
            'name_de' => $area[1],
            'translation_key' => $area[2],
            'group' => 'Shift settings',
            'tooltipText' => $area[3],
            'tooltipKey' => $area[3],
            'checked' => true,
        ], $areas);

        $definitions[] = [
            'name' => PermissionEnum::CAN_VIEW_OWN_UNCOMMITTED_SHIFTS->value,
            'name_de' => 'Eigene nicht festgeschriebene Schichten sehen',
            'translation_key' => 'View own uncommitted shifts',
            'group' => 'Shifts',
            'tooltipText' => 'Erlaubt einer Person, eigene noch nicht festgeschriebene Schichten weiterhin im Einsatzplan zu sehen, wenn die hausweite Ausblendung aktiv ist.',
            'tooltipKey' => 'Allows a person to continue seeing their own uncommitted shifts when instance-wide hiding is enabled.',
            'checked' => false,
        ];

        return $definitions;
    }

    public function grantGranularDefaultsToMasterPermissionHolders(): void
    {
        foreach (self::definitions() as $definition) {
            Permission::query()->updateOrCreate(
                ['name' => $definition['name'], 'guard_name' => 'web'],
                collect($definition)->except('name')->all()
            );
        }

        $masterPermission = Permission::query()
            ->where('name', PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value)
            ->first();

        if ($masterPermission === null) {
            return;
        }

        $childPermissions = Permission::query()
            ->whereIn('name', $this->granularPermissionNames())
            ->get();

        foreach ($masterPermission->roles as $role) {
            $role->givePermissionTo($childPermissions);

            foreach ($role->users as $user) {
                $user->forgetCachedShareData();
            }
        }

        foreach ($masterPermission->users as $user) {
            $user->givePermissionTo($childPermissions);
            $user->forgetCachedShareData();
        }

        $masterPermissionId = $masterPermission->getKey();
        $childPermissionIds = $childPermissions->modelKeys();

        PermissionPreset::query()->eachById(function (PermissionPreset $preset) use ($masterPermissionId, $childPermissionIds): void {
            $permissionIds = array_map('intval', $preset->permissions ?? []);
            if (!in_array((int) $masterPermissionId, $permissionIds, true)) {
                return;
            }

            $preset->update([
                'permissions' => array_values(array_unique([...$permissionIds, ...$childPermissionIds])),
            ]);
        });
    }

    /**
     * @return array<int, string>
     */
    private function granularPermissionNames(): array
    {
        return collect(self::definitions())
            ->pluck('name')
            ->filter(static fn (string $name): bool => str_starts_with($name, 'shift.settings.'))
            ->reject(static fn (string $name): bool => $name === PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value)
            ->values()
            ->all();
    }
}
