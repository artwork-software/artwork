<?php

namespace Artwork\Modules\Project\Repositories;

use App\Models\User;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository
{
    public function findManagers(Project $project): Collection
    {
        return $project->users()->wherePivot('is_manager', '=', 1)->get();
    }

    public function findUsers(Project $project): Collection
    {
        return $project->users()->get();
    }

    public function findById(int $id): Project
    {
        return Project::findOrFail($id);
    }

    public function getProjectsByCostCenter(string $costCenter): Collection
    {
        return Project::whereHas('cost_center', function ($query) use ($costCenter): void {
            $query->where('name', '=', $costCenter);
        })->without(['shiftRelevantEventTypes', 'state'])
            ->with(['table', 'table.mainPositions.subPositions.subPositionRows.cells'])->get();
    }
}
