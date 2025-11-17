<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;

class ProjectTabArtistNameService
{
    public function buildArtistNamePayload(Project $project): array
    {
        return [
            'artist_name' => $project->artists,
        ];
    }
}

