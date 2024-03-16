<?php

namespace Artwork\Modules\Setup\Database;

use Artwork\Modules\Permission\Service\PermissionService;
use Artwork\Modules\Permission\Service\RoleService;
use Artwork\Modules\Setup\DataProvider\RoleAndPermissionDataProvider;
use Illuminate\Support\Facades\DB;

trait ModifiesBaseData
{
    public function modifyBaseData(): void
    {
        if (!$this->isInstalled()) {
            return;
        }
        /** @var RoleAndPermissionDataProvider $dataProvider */
        $dataProvider = app()->get(RoleAndPermissionDataProvider::class);
        /** @var PermissionService $service */
        $service = app()->get(PermissionService::class);
        foreach ($dataProvider->getPermissions() as $permission) {
            $this->modifyEntry($service, $permission);
        }
        $service = app()->get(RoleService::class);
        foreach ($dataProvider->getRoles() as $permission) {
            $this->modifyEntry($service, $permission);
        }
    }

    private function isInstalled(): bool
    {
        return DB::table('permissions')->count() > 0;
    }

    private function modifyEntry(PermissionService|RoleService $service, array $data): void
    {
        if (!$entry = $service->findByName($data['name'])) {
            $service->createFromArray($data);
            return;
        }
        foreach (array_keys($data) as $key) {
            $entry->{$key} = $data[$key];
        }
        $service->save($entry);
    }
}
