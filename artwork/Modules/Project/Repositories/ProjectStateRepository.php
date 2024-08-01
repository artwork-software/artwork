<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Models\ProjectState;
use Illuminate\Support\Collection;

class ProjectStateRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ProjectState::all();
    }
}
