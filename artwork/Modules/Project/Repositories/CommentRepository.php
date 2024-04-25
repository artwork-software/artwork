<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Collection;

readonly class CommentRepository extends BaseRepository
{
    public function findForProject(Project $project): Collection
    {
        return $project->comments;
    }
}
