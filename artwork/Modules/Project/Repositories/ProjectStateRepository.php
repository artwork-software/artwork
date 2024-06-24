<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Models\ProjectStates;
use Illuminate\Support\Collection;

readonly class ProjectStateRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ProjectStates::all();
    }
}
