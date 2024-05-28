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
        DB::table('project_user')->whereJsonContains('roles', $role->id)->update([
            'roles' => DB::raw('JSON_REMOVE(roles, "$[0]")')
        ]);
        $this->projectRoleRepository->delete($role);
    }
}
