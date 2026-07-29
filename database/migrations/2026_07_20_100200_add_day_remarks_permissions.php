<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill der Tagesbemerkungs-Permissions für Bestandsinstanzen (Greenfield-Installs
 * bekommen sie über den Seeder). Die View-Permission wird einmalig allen bestehenden
 * Usern zugewiesen (Produktentscheidung: Spalte ist nach Aktivierung des Features für
 * alle sichtbar); spätere manuelle Entziehungen bleiben unangetastet, da diese
 * Migration nur einmal läuft.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = collect((new BaseDataProvider())->getPermissions());

        foreach ([PermissionEnum::DAY_REMARKS_VIEW, PermissionEnum::DAY_REMARKS_EDIT] as $permissionEnum) {
            $definition = $definitions->firstWhere('name', $permissionEnum->value);

            if ($definition === null) {
                continue;
            }

            Permission::firstOrCreate(
                ['name' => $definition['name']],
                $definition,
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        User::query()->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $user->givePermissionTo(PermissionEnum::DAY_REMARKS_VIEW->value);
            }
        });
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', [PermissionEnum::DAY_REMARKS_VIEW->value, PermissionEnum::DAY_REMARKS_EDIT->value])
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
