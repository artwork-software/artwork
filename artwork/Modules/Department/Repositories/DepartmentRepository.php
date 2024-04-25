<?php

namespace Artwork\Modules\Department\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Collection;

readonly class DepartmentRepository extends BaseRepository
{
    /**
     * @param string $query
     * @return Collection
     */
    public function searchDepartments(string $query): Collection
    {
        return Department::search($query)->get();
    }

    /**
     * @return Collection
     */
    public function getAllDepartments(): Collection
    {
        return Department::all();
    }

    /**
     * @param Department $department
     * @return Collection
     */
    public function getDepartmentUsers(Department $department): Collection
    {
        return $department->users()->get();
    }

    /**
     * @param Department $department
     * @param array $userIds
     * @return array<int, int>
     */
    public function syncUsers(Department $department, array $userIds): array
    {
        return $department->users()->sync($userIds);
    }
}
