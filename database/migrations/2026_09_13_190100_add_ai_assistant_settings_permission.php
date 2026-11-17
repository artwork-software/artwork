<?php

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
            ->firstWhere('name', 'change ai assistant settings');

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
            ->where('name', 'change ai assistant settings')
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
