<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definition = collect((new BaseDataProvider())->getPermissions())
            ->firstWhere('name', PermissionEnum::TICKETING_MOVE_ON_SALE->value);

        if ($definition !== null) {
            Permission::firstOrCreate(
                ['name' => $definition['name']],
                $definition,
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', PermissionEnum::TICKETING_MOVE_ON_SALE->value)
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
