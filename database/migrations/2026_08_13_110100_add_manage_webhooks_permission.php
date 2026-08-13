<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill der Webhook-Berechtigung für Bestandsinstanzen (Neuinstallationen bekommen sie über den
 * Seeder). Anders als bei den Tagesbemerkungen wird sie bewusst niemandem automatisch zugewiesen:
 * Webhooks senden Daten aus artwork hinaus, das gehört ausdrücklich vergeben. Admins kommen ohnehin
 * über den Gate::before-Bypass daran.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definition = collect((new BaseDataProvider())->getPermissions())
            ->firstWhere('name', PermissionEnum::WEBHOOKS_MANAGE->value);

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
            ->where('name', PermissionEnum::WEBHOOKS_MANAGE->value)
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
