<?php

namespace Artwork\Modules\ArtistResidency\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Illuminate\Database\Eloquent\Collection;

class ArtistResidencyRepository extends BaseRepository
{

    public function getArtistResidencyByProjectId(int $projectId): Collection
    {
        return ArtistResidency::where('project_id', $projectId)->get();
    }
}
