<?php

namespace Tests\Feature\Projects\ContentExport;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\FeatureTestCase;

/**
 * Einmalige Vergabe von "Exporte aus der Projektübersicht" an alle mit einem Recht, das alle Projekte öffnet.
 */
final class GrantProjectOverviewExportPermissionMigrationTest extends FeatureTestCase
{
    private const EXPORT = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;

    #[Test]
    public function holders_of_global_project_permissions_roles_and_presets_get_the_export_permission(): void
    {
        foreach (['view projects', 'write projects', 'delete projects', self::EXPORT] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        DB::table('model_has_permissions')
            ->where('permission_id', Permission::findByName(self::EXPORT, 'web')->id)
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $reader = User::factory()->create();
        $reader->givePermissionTo('view projects');
        $writer = User::factory()->create();
        $writer->givePermissionTo('write projects');
        $teamOnly = User::factory()->create();
        $role = Role::findOrCreate('Export test role', 'web');
        $role->givePermissionTo('view projects');
        $presetId = DB::table('permission_presets')->insertGetId([
            'name' => 'Reader preset',
            'permissions' => json_encode(['view projects']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put("user:{$reader->id}:roles_permissions", [['stale'], ['stale'], ['stale']], 300);

        $migration = require database_path('migrations/2026_11_09_123007_grant_project_overview_export_permission.php');
        $migration->up();
        $migration->up(); // idempotent

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue($reader->fresh()->hasDirectPermission(self::EXPORT));
        $this->assertTrue($writer->fresh()->hasDirectPermission(self::EXPORT));
        $this->assertFalse($teamOnly->fresh()->hasDirectPermission(self::EXPORT));
        $this->assertFalse(Cache::has("user:{$reader->id}:roles_permissions"), 'Frontend-Rechte-Cache geleert');
        $this->assertTrue($role->fresh()->hasPermissionTo(self::EXPORT));
        $this->assertSame(
            ['view projects', self::EXPORT],
            json_decode((string) DB::table('permission_presets')->where('id', $presetId)->value('permissions'), true)
        );
    }
}
