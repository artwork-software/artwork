<?php

namespace Artwork\Modules\Permission\Services;

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Backend-Implikation der Stufenleiter: "das stärkste Recht setzt die kleineren Stufen".
 * Wird beim Speichern (Nutzerrechte, Einladung, Presets) und einmalig für Bestandsumgebungen
 * (artwork:permissions:apply-implications) angewendet.
 */
readonly class PermissionImplicationService
{
    public function __construct(
        private PermissionCatalog $catalog,
        private PermissionChangeLogService $changeLog,
    ) {
    }

    /**
     * @param iterable<string> $names
     * @return string[] nur Rechte, die es im Katalog UND in der Datenbank gibt
     */
    public function expand(iterable $names): array
    {
        $expanded = $this->catalog->expandWithImplied($names);
        $existing = Permission::query()->whereIn('name', $expanded)->pluck('name')->all();

        return array_values(array_intersect($expanded, $existing));
    }

    /**
     * Ergänzt fehlende implizierte Rechte bei allen Personen, Rollen und Presets.
     *
     * @return array{users: int, roles: int, presets: int}
     */
    public function applyToAll(): array
    {
        $touchedUsers = 0;
        $touchedRoles = 0;

        $handleUsers = function (Collection $users) use (&$touchedUsers): void {
            foreach ($users as $user) {
                $current = $user->permissions->pluck('name')->all();
                $expanded = $this->expand($current);
                $missing = array_diff($expanded, $current);
                if ($missing === []) {
                    continue;
                }
                $user->givePermissionTo($missing);
                $user->forgetCachedShareData();
                // Ergänzte Stufen sichtbar machen: Verlauf auf der Rechteseite (Quelle "implication")
                $roles = $user->roles->pluck('name')->all();
                $after = [...$current, ...array_values($missing)];
                $this->changeLog->log($user, null, $current, $after, $roles, $roles, 'implication');
                $touchedUsers++;
            }
        };
        User::query()->with(['permissions', 'roles'])->chunkById(200, $handleUsers);

        foreach (Role::query()->with('permissions')->get() as $role) {
            $current = $role->permissions->pluck('name')->all();
            $expanded = $this->expand($current);
            $missing = array_diff($expanded, $current);
            if ($missing === []) {
                continue;
            }
            $role->givePermissionTo($missing);
            $touchedRoles++;
        }

        $touchedPresets = app(PermissionPresetService::class)->applyImplicationsToAll();

        return ['users' => $touchedUsers, 'roles' => $touchedRoles, 'presets' => $touchedPresets];
    }
}
