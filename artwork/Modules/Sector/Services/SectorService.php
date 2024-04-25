<?php

namespace Artwork\Modules\Sector\Services;

use Artwork\Modules\Sector\Repositories\SectorRepository;

readonly class SectorService
{
    public function __construct(private SectorRepository $sectorRepository)
    {
    }
}
