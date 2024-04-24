<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Repositories\ProjectFileRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectFileService
{
    public function __construct(private ProjectFileRepository $projectFileRepository)
    {
    }

    public function deleteAll(Collection|array $projectFiles): void
    {
        /** @var ProjectFile $projectFile */
        foreach ($projectFiles as $projectFile) {
            $this->projectFileRepository->delete($projectFile);
        }
    }

    public function restoreAll(Collection|array $projectFiles): void
    {
        /** @var ProjectFile $projectFile */
        foreach ($projectFiles as $projectFile) {
            $this->projectFileRepository->restore($projectFile);
        }
    }

    public function forceDeleteAll(Collection|array $projectFiles): void
    {
        /** @var ProjectFile $projectFile */
        foreach ($projectFiles as $projectFile) {
            $this->projectFileRepository->forceDelete($projectFile);
        }
    }

    public function restore(ProjectFile $projectFile): bool
    {
        return $this->projectFileRepository->restore($projectFile);
    }

    public function forceDelete(ProjectFile $projectFile): bool
    {
        return $this->projectFileRepository->forceDelete($projectFile);
    }
}
