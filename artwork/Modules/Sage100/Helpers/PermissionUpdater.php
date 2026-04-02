<?php

namespace Artwork\Modules\Sage100\Helpers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionUpdater
{
    private const LEGACY_PERMISSION_NAME = 'can view and delete sage100-api-data';

    public function seed(): void
    {
        $this->ensureNewPermissionsExist();
        $this->migrateAndRemoveLegacyPermission();
    }

    private function ensureNewPermissionsExist(): void
    {
        $dataProvider = new BaseDataProvider();
        $permissions = $dataProvider->getPermissions();

        foreach ($permissions as $permissionData) {
            if (
                $permissionData['name'] === PermissionEnum::VIEW_PROJECT_SAGE_DATA->value ||
                $permissionData['name'] === PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value
            ) {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name'], 'guard_name' => 'web'],
                    $permissionData
                );
            }
        }
    }

    private function migrateAndRemoveLegacyPermission(): void
    {
        $oldPermission = Permission::where('name', self::LEGACY_PERMISSION_NAME)
            ->where('guard_name', 'web')
            ->first();

        if (!$oldPermission) {
            return;
        }

        $newProjectPermission = Permission::where('name', PermissionEnum::VIEW_PROJECT_SAGE_DATA->value)
            ->where('guard_name', 'web')
            ->first();
        $newGlobalPermission = Permission::where('name', PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value)
            ->where('guard_name', 'web')
            ->first();

        if ($newProjectPermission && $newGlobalPermission) {
            $usersWithOldPermission = DB::table('model_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->where('model_type', User::class)
                ->pluck('model_id');

            foreach ($usersWithOldPermission as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->givePermissionTo([
                        $newProjectPermission->name,
                        $newGlobalPermission->name,
                    ]);
                }
            }
        }

        DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->delete();

        $oldPermission->delete();
    }
}
