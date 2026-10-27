<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/** Artwork-Tickets im Hauptmenü (Verkäufe, Karten & Abos) und der Einlass an der Tür. */
return new class extends Migration
{
    private const PERMISSIONS = [PermissionEnum::TICKETING_VIEW_SALES, PermissionEnum::TICKETING_ADMISSION];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = collect((new BaseDataProvider())->getPermissions())->keyBy('name');

        foreach (self::PERMISSIONS as $permission) {
            $definition = $definitions->get($permission->value);

            if ($definition !== null) {
                Permission::firstOrCreate(['name' => $definition['name']], $definition);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', array_map(static fn (PermissionEnum $permission): string => $permission->value, self::PERMISSIONS))
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
