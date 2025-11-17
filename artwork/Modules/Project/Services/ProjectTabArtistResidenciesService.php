<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\ArtistResidency\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Project\Models\Project;

class ProjectTabArtistResidenciesService
{
    public function buildArtistResidenciesPayload(Project $project): array
    {
        return [
            'artists' => Artist::all(),
            'accommodations' => Accommodation::with('roomTypes')->get(),
            'artist_residencies' => $project->artistResidencies()
                ->with(['accommodation', 'accommodation.roomTypes', 'artist', 'roomType'])
                ->get(),
        ];
    }
}

