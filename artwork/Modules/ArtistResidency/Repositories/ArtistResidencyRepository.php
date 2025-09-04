<?php

namespace Artwork\Modules\ArtistResidency\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Illuminate\Database\Eloquent\Collection;

class ArtistResidencyRepository
{

    public function getArtistResidencyByProjectId(int $projectId): Collection
    {
        return ArtistResidency::where('project_id', $projectId)->get();
    }

    public function create(array $data): ArtistResidency
    {
        return ArtistResidency::create($data);
    }

    public function update(ArtistResidency $residency, array $data): ArtistResidency
    {
        if (!empty($data)) {
            $residency->update($data);
        }
        return $residency->refresh();
    }

    public function associateArtist(ArtistResidency $residency, Artist $artist): ArtistResidency
    {
        $residency->artist()->associate($artist);
        $residency->save();

        return $residency->refresh();
    }

    public function dissociateArtist(ArtistResidency $residency): ArtistResidency
    {
        $residency->artist()->dissociate();
        $residency->save();

        return $residency->refresh();
    }
}
