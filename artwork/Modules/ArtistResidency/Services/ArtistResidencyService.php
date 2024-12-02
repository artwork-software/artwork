<?php

namespace Artwork\Modules\ArtistResidency\Services;

use Artwork\Modules\ArtistResidency\Repositories\ArtistResidencyRepository;

readonly class ArtistResidencyService
{
    public function __construct(
        private readonly ArtistResidencyRepository $ArtistResidencyRepository
    ) {
    }
}
