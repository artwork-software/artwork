<?php

namespace Artwork\Modules\Setup\Database;

use Artwork\Modules\Permission\Services\PermissionService;
use Artwork\Modules\Permission\Services\RoleService;
use Artwork\Modules\Setup\DataProvider\RoleAndPermissionDataProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $tableFields = $service->getTableFields();

        foreach ($dataProvider->getPermissions() as $permission) {
            $this->modifyEntry($service, $permission, $tableFields, $dataProvider->getExcludedPermissionColumns());
        }

        /** @var RoleService $service */
        $service = app()->get(RoleService::class);
        foreach ($dataProvider->getRoles() as $role) {
            $this->modifyEntry($service, $role, $tableFields, $dataProvider->getExcludedRoleColumns());
        }
    }

    private function isInstalled(): bool
    {
        return DB::table('permissions')->count() > 0;
    }

    private function getValidKeys(array $tableFields, array $exclusionKeys): array
    {
        $keys = [];
        foreach ($tableFields as $fieldObject) {
            $field = $fieldObject->Field;
            if (Str::contains($field, $exclusionKeys)) {
                continue;
            }
            $keys[] = $field;
        }

        return $keys;
    }

    private function modifyEntry(
        PermissionService|RoleService $service,
        array $data,
        array $tableFields,
        array $exclusionKeys
    ): void {
        $validKeys = $this->getValidKeys($tableFields, $exclusionKeys);
        $data = Arr::only($data, $validKeys);
        try {
            if (!$entry = $service->findByName($data['name'])) {
                $service->createFromArray($data);
                return;
            }
            foreach (array_keys($data) as $key) {
                $entry->{$key} = $data[$key];
            }
            $service->save($entry);
        } catch (QueryException $e) {
            //If called early this might attempt to write to columns that don't exist yet
            report($e);
        }
    }
}
