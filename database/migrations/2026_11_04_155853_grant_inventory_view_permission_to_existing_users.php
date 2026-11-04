<?php

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Die Inventarübersicht braucht seit 04.11.2026 das Recht "inventory.view" (Gate view-inventory). Bis dahin sah
 * sie jede Person, sobald das Modul aktiv war. Damit sich für Bestandsumgebungen nichts still ändert, bekommen
 * alle vorhandenen Personen das Recht einmalig explizit; Admins können es danach gezielt entziehen.
 *
 * Läuft in artwork:update VOR artwork:update-permissions, daher legt die Migration das Recht selbst an. Auf
 * frischen Instanzen (noch keine Rechte angelegt) passiert nichts: dort seedet der Katalog später alle Rechte,
 * und Personen gibt es noch nicht. Idempotent: vorhandene Zuordnungen werden übersprungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->exists()) {
            return;
        }

        $permissionId = $this->findOrCreatePermissionId();

        $userMorphClass = (new User())->getMorphClass();
        DB::table('users')->select('id')->orderBy('id')->chunkById(500, function ($users) use (
            $permissionId,
            $userMorphClass
        ): void {
            DB::table('model_has_permissions')->insertOrIgnore(
                $users->map(static fn (object $user): array => [
                    'permission_id' => $permissionId,
                    'model_type' => $userMorphClass,
                    'model_id' => $user->id,
                ])->all()
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Bewusst leer: das Recht wurde explizit vergeben und soll bei einem Rollback nicht still verschwinden.
    }

    private function findOrCreatePermissionId(): int
    {
        $name = PermissionEnum::INVENTORY_VIEW->value;
        $existingId = DB::table('permissions')->where('name', $name)->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }

        $row = collect(app(PermissionCatalog::class)->seedRows())->firstWhere('name', $name);

        return (int) DB::table('permissions')->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'translation_key' => $row['translation_key'] ?? 'View inventory',
            'group' => $row['group'] ?? 'Inventory & material issues',
            'tooltipKey' => $row['tooltipKey'] ?? null,
            'checked' => $row['checked'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
