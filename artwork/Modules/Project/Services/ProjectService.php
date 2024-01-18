<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectStoreRequest;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectFileRepository;
use Artwork\Modules\Project\Repositories\ProjectHeadlineRepository;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Project\Repositories\ProjectStateRepository;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    public function __construct(
        private readonly ProjectFileRepository     $projectFileRepository,
        private readonly ProjectHeadlineRepository $projectHeadlineRepository,
        private readonly ProjectStateRepository    $projectStateRepository,
        private readonly ProjectRepository         $projectRepository,
    )
    {
    }


    public function storeByRequest(ProjectStoreRequest $projectStoreRequest): void
    {

    }

    public function pin(Project $project): bool
    {
        $user = Auth::user();
        $pinnedByUsers = $project->pinned_by_users;

        if (is_null($pinnedByUsers)) {
            $pinnedByUsers = [];
        }
        if (in_array($user->id, $pinnedByUsers)) {
            $pinnedByUsers = array_diff($pinnedByUsers, [$user->id]);
        } else {
            $pinnedByUsers[] = $user->id;
        }
        return $project->update(['pinned_by_users' => $pinnedByUsers]);
    }
}
