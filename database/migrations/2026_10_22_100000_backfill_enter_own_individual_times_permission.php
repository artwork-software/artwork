<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Eigene individuelle Zeiten brauchen seit 16.10.2026 das Recht "can enter own individual times"
 * (OwnIndividualTimeAccess). Bis dahin durfte jede Person, die ihren eigenen Einsatzplan sieht, ihre eigenen
 * Zeiten bearbeiten. Damit sich für Bestandsumgebungen nichts still ändert, bekommen alle Personen, Rollen und
 * Presets mit "can view own roster" das neue Recht einmalig explizit; Admins können es danach gezielt entziehen.
 * Idempotent: bereits vorhandene Zuordnungen werden übersprungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $source = PermissionEnum::CAN_VIEW_OWN_ROSTER->value;
        $target = PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value;

        $sourceId = DB::table('permissions')->where('name', $source)->value('id');
        $targetId = DB::table('permissions')->where('name', $target)->value('id');
        if (!$sourceId || !$targetId) {
            return; // Rechte werden erst vom UpdatePermissionsCommand angelegt (frische Instanz) – nichts zu übertragen
        }

        // Personen (spatie model_has_permissions)
        $holders = DB::table('model_has_permissions')->where('permission_id', $sourceId)->get();
        foreach ($holders as $holder) {
            $exists = DB::table('model_has_permissions')
                ->where('permission_id', $targetId)
                ->where('model_type', $holder->model_type)
                ->where('model_id', $holder->model_id)
                ->exists();
            if (!$exists) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $targetId,
                    'model_type' => $holder->model_type,
                    'model_id' => $holder->model_id,
                ]);
            }
        }

        $roleIds = DB::table('role_has_permissions')->where('permission_id', $sourceId)->pluck('role_id');
        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $targetId)
                ->where('role_id', $roleId)
                ->exists();
            if (!$exists) {
                DB::table('role_has_permissions')->insert(['permission_id' => $targetId, 'role_id' => $roleId]);
            }
        }

        // Presets speichern Rechte-Namen als JSON-Liste (Altbestände ggf. noch IDs)
        foreach (DB::table('permission_presets')->get() as $preset) {
            $names = json_decode((string) $preset->permissions, true) ?: [];
            $numericIds = array_map('intval', array_filter($names, 'is_numeric'));
            $hasSource = in_array($source, $names, true) || in_array((int) $sourceId, $numericIds, true);
            $hasTarget = in_array($target, $names, true) || in_array((int) $targetId, $numericIds, true);
            if ($hasSource && !$hasTarget) {
                $names[] = $target;
                DB::table('permission_presets')->where('id', $preset->id)->update([
                    'permissions' => json_encode(array_values($names), JSON_THROW_ON_ERROR),
                ]);
            }
        }

        // Spatie-Permission-Cache leeren, damit die neuen Zuordnungen sofort gelten
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Bewusst leer: das Recht wurde explizit vergeben und soll bei einem Rollback nicht still verschwinden.
    }
};
