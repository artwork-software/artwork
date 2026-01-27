<?php

namespace Artwork\Modules\Sage100\Helpers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionUpdater
{
    public function seed(): void
    {
        $newProjectPermission = Permission::where('name', PermissionEnum::VIEW_PROJECT_SAGE_DATA->value)
            ->where('guard_name', 'web')
            ->first();
        $newGlobalPermission = Permission::where('name', PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value)
            ->where('guard_name', 'web')
            ->first();

        if ($newProjectPermission && $newGlobalPermission) {
            $usersWithNewPermissions = DB::table('model_has_permissions')
                ->whereIn('permission_id', [$newProjectPermission->id, $newGlobalPermission->id])
                ->where('model_type', User::class)
                ->distinct()
                ->pluck('model_id');

            if ($usersWithNewPermissions->isNotEmpty()) {
                return;
            }
        }

        $dataProvider = new BaseDataProvider();
        $permissions = $dataProvider->getPermissions();

        $projectSagePermission = null;
        $globalSagePermission = null;

        foreach ($permissions as $permissionData) {
            if ($permissionData['name'] === PermissionEnum::VIEW_PROJECT_SAGE_DATA->value) {
                $projectSagePermission = $permissionData;
            }
            if ($permissionData['name'] === PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value) {
                $globalSagePermission = $permissionData;
            }
        }

        if ($projectSagePermission) {
            Permission::firstOrCreate(
                ['name' => $projectSagePermission['name'], 'guard_name' => 'web'],
                $projectSagePermission
            );
        }

        if ($globalSagePermission) {
            Permission::firstOrCreate(
                ['name' => $globalSagePermission['name'], 'guard_name' => 'web'],
                $globalSagePermission
            );
        }

        $oldPermission = Permission::where('name', PermissionEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value)
            ->where('guard_name', 'web')
            ->first();

        if ($oldPermission) {
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
                            $newGlobalPermission->name
                        ]);
                    }
                }
            }
        }
    }
}
