<?php

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Konzept Nutzerrechte (02.09.2026), Phase 2:
 * 1. neue Rechte aus dem Katalog anlegen (Kalender-/Finanzierungsquellen-/Budget-Einstellungen, Papierkorb)
 * 2. "Globaler Budgetzugriff ohne Dokumenteneinsicht" in "Globaler Budgetzugriff" aufgehen lassen
 * 3. entfallene Rechte löschen (tot, wirkungslos, Altbestand Gewerke-Inventar, externe Konditionen)
 * 4. Presets von Permission-IDs auf Rechte-Namen umstellen
 * 5. offene Einladungen bereinigen (sonst wirft das Annehmen PermissionDoesNotExist)
 */
return new class extends Migration
{
    private const MERGED = [
        // entfallenes Recht => Nachfolger, den die Inhaber bekommen
        'can manage all project budgets without docs' => 'can manage global project budgets',
        'can edit external users conditions' => 'can manage external workers',
    ];

    private const REMOVED = [
        'can see, edit and delete project contracts and docs',
        'can use checklists',
        'can create events when creating a project',
        'can manage inventory stock',
        'can plan inventory',
        'can manage all project budgets without docs',
        'can edit external users conditions',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. neue Rechte
        foreach (app(PermissionCatalog::class)->seedRows() as $row) {
            Permission::query()->firstOrCreate(['name' => $row['name']], $row);
        }

        // 2. Zusammenlegungen: Inhaber des alten Rechts bekommen den Nachfolger
        foreach (self::MERGED as $old => $successor) {
            $oldPermission = Permission::query()->where('name', $old)->first();
            $newPermission = Permission::query()->where('name', $successor)->first();
            if ($oldPermission === null || $newPermission === null) {
                continue;
            }
            foreach (['model_has_permissions' => ['model_type', 'model_id'], 'role_has_permissions' => ['role_id']] as $table => $columns) {
                $rows = DB::table($table)->where('permission_id', $oldPermission->id)->get();
                foreach ($rows as $row) {
                    $key = ['permission_id' => $newPermission->id];
                    foreach ($columns as $column) {
                        $key[$column] = $row->{$column};
                    }
                    if (!DB::table($table)->where($key)->exists()) {
                        DB::table($table)->insert($key);
                    }
                }
            }
        }

        // 4a. Presets: IDs -> Namen (vor dem Löschen, damit IDs noch auflösbar sind)
        foreach (PermissionPreset::query()->get() as $preset) {
            $names = array_values(array_diff($preset->permissionNames(), self::REMOVED));
            foreach (self::MERGED as $old => $successor) {
                if (in_array($old, $preset->permissionNames(), true) && !in_array($successor, $names, true)) {
                    $names[] = $successor;
                }
            }
            $preset->update(['permissions' => $names]);
        }

        // 5. offene Einladungen: entfallene Rechte entfernen, zusammengelegte ersetzen
        foreach (DB::table('invitations')->select(['id', 'permissions'])->get() as $invitation) {
            $names = json_decode((string) $invitation->permissions, true);
            if (!is_array($names)) {
                continue;
            }
            $cleaned = array_values(array_diff($names, self::REMOVED));
            foreach (self::MERGED as $old => $successor) {
                if (in_array($old, $names, true) && !in_array($successor, $cleaned, true)) {
                    $cleaned[] = $successor;
                }
            }
            if ($cleaned !== $names) {
                DB::table('invitations')->where('id', $invitation->id)->update(['permissions' => json_encode($cleaned)]);
            }
        }

        // 3. entfallene Rechte löschen (Pivots hängen per FK CASCADE daran)
        foreach (self::REMOVED as $name) {
            $permission = Permission::query()->where('name', $name)->first();
            if ($permission === null) {
                continue;
            }
            DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Datenmigration ohne Rückweg: entfallene Rechte werden nicht wiederhergestellt.
    }
};
