<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Repositories\ProjectRoleRepository;
use Illuminate\Support\Facades\DB;

class ProjectRoleService
{
    public function __construct(
        private ProjectRoleRepository $projectRoleRepository
    ) {
    }


    public function createByRequest(array $data): void
    {
        $newRole = new ProjectRole();
        $newRole->name = $data['name'];
        $this->projectRoleRepository->save($newRole);
    }

    public function updateByRequest(ProjectRole $role, array $data): void
    {
        $role->name = $data['name'];
        $this->projectRoleRepository->save($role);
    }

    public function delete(ProjectRole $role): void
    {
        DB::transaction(function () use ($role): void {
            DB::table('project_user')
                ->whereJsonContains('roles', $role->id)
                ->lockForUpdate()
                ->get(['id', 'roles'])
                ->each(function ($row) use ($role): void {
                    $remainingRoleIds = array_values(
                        array_diff(json_decode($row->roles, true) ?? [], [$role->id])
                    );

                    DB::table('project_user')
                        ->where('id', $row->id)
                        ->update(['roles' => json_encode($remainingRoleIds)]);
                });

            $this->projectRoleRepository->delete($role);
        });
    }
}
