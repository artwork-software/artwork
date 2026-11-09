<?php

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seit 11/2026 steuert das Recht "Exporte aus der Projektübersicht" den Export-Button der Projektübersicht
 * (Terminliste, Kalender, Budget nach Stichtag, Rollenmatrix, Projektinhalte als Excel/Word). Einmalig
 * bekommen es alle Personen, Rollen und Rechte-Vorlagen mit "Leserechte für alle Projekte" oder einem Recht,
 * das alle Projekte öffnet (Schreib-/Löschrecht für alle Projekte). Danach wird es gezielt vergeben.
 *
 * Läuft in artwork:update VOR artwork:update-permissions, daher legt die Migration das Recht selbst an. Auf
 * frischen Instanzen (noch keine Rechte angelegt) passiert nichts. Idempotent.
 */
return new class extends Migration
{
    private const SOURCES = [
        'view projects',
        'write projects',
        'delete projects',
    ];

    public function up(): void
    {
        if (!DB::table('permissions')->exists()) {
            return;
        }

        $sourceIds = DB::table('permissions')->whereIn('name', self::SOURCES)->pluck('id')->map(
            static fn ($id): int => (int) $id
        );
        if ($sourceIds->isEmpty()) {
            return;
        }

        $targetId = $this->findOrCreatePermissionId();

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $sourceIds)
            ->select(['model_type', 'model_id'])
            ->distinct()
            ->get()
            ->chunk(500)
            ->each(static function ($holders) use ($targetId): void {
                DB::table('model_has_permissions')->insertOrIgnore(
                    $holders->map(static fn (object $holder): array => [
                        'permission_id' => $targetId,
                        'model_type' => $holder->model_type,
                        'model_id' => $holder->model_id,
                    ])->values()->all()
                );
            });

        $roleIds = DB::table('role_has_permissions')->whereIn('permission_id', $sourceIds)->distinct()->pluck('role_id');
        DB::table('role_has_permissions')->insertOrIgnore(
            $roleIds->map(static fn ($roleId): array => [
                'permission_id' => $targetId,
                'role_id' => $roleId,
            ])->all()
        );

        $this->grantToPresets($targetId, $sourceIds->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->forgetSharedPermissionsOfHolders($targetId);
    }

    /**
     * Die Rechte fürs Frontend sind je Person 5 Minuten gecacht (HandleInertiaRequests): ohne Leeren erschiene der
     * Export-Button erst verzögert.
     */
    private function forgetSharedPermissionsOfHolders(int $targetId): void
    {
        $userMorphClass = (new User())->getMorphClass();
        $roleIds = DB::table('role_has_permissions')->where('permission_id', $targetId)->pluck('role_id');

        DB::table('model_has_permissions')
            ->where('permission_id', $targetId)
            ->where('model_type', $userMorphClass)
            ->pluck('model_id')
            ->merge(
                DB::table('model_has_roles')
                    ->whereIn('role_id', $roleIds)
                    ->where('model_type', $userMorphClass)
                    ->pluck('model_id')
            )
            ->unique()
            ->chunk(500)
            ->each(static fn ($userIds) => User::forgetCachedShareDataForIds($userIds->all()));
    }

    /**
     * Vorlagen speichern Rechte-Namen als JSON-Liste, ältere teils IDs.
     *
     * @param array<int, int> $sourceIds
     */
    private function grantToPresets(int $targetId, array $sourceIds): void
    {
        if (!Schema::hasTable('permission_presets')) {
            return;
        }

        $target = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;
        foreach (DB::table('permission_presets')->get(['id', 'permissions']) as $preset) {
            $permissions = json_decode((string) $preset->permissions, true) ?: [];
            $numericIds = array_map('intval', array_filter($permissions, 'is_numeric'));
            $hasSource = array_intersect(self::SOURCES, $permissions) !== [] ||
                array_intersect($sourceIds, $numericIds) !== [];
            $hasTarget = in_array($target, $permissions, true) || in_array($targetId, $numericIds, true);
            if (!$hasSource || $hasTarget) {
                continue;
            }
            $permissions[] = $target;
            DB::table('permission_presets')->where('id', $preset->id)->update([
                'permissions' => json_encode(array_values($permissions), JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function findOrCreatePermissionId(): int
    {
        $name = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;
        $existingId = DB::table('permissions')->where('name', $name)->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }

        $row = collect(app(PermissionCatalog::class)->seedRows())->firstWhere('name', $name);

        return (int) DB::table('permissions')->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'translation_key' => $row['translation_key'] ?? 'Exports from the project overview',
            'group' => $row['group'] ?? 'Projects',
            'tooltipKey' => $row['tooltipKey'] ?? null,
            'checked' => $row['checked'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Bewusst leer: das Recht wurde explizit vergeben und soll bei einem Rollback nicht still verschwinden.
    }
};
