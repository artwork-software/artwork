<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt fehlende Rechte an und aktualisiert Gruppe/Übersetzungsschlüssel aus dem Rechte-Katalog.
 * Löscht nichts — entfallene Rechte werden per Migration entfernt.
 */
class UpdatePermissionsCommand extends Command
{
    protected $signature = 'artwork:update-permissions';
    protected $description = 'Update the permissions table from the permission catalog';

    public function handle(PermissionCatalog $catalog): void
    {
        foreach ($catalog->seedRows() as $row) {
            $permission = Permission::query()->where('name', $row['name'])->first();
            if ($permission === null) {
                Permission::create($row);
                $this->info('Permission "' . $row['name'] . '" created.');
                continue;
            }

            $permission->update([
                'translation_key' => $row['translation_key'],
                'group' => $row['group'],
                'tooltipKey' => $row['tooltipKey'],
                'checked' => $row['checked'],
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Permissions synced with catalog (' . count($catalog->seedRows()) . ').');
    }
}
