<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pulls the new INVITE_EXTERNAL permission into existing instances that no longer run the
 * setup seeder. Greenfield installs get it from RolesAndPermissionsSeeder; this migration is
 * the idempotent backfill. Mirrors the seeder by reusing the BaseDataProvider definition so
 * name_de/group/tooltip stay a single source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definition = collect((new BaseDataProvider())->getPermissions())
            ->firstWhere('name', PermissionEnum::INVITE_EXTERNAL->value);

        if ($definition === null) {
            return;
        }

        Permission::firstOrCreate(
            ['name' => $definition['name']],
            $definition,
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
