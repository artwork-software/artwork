<?php

namespace Artwork\Modules\Sector\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Sector\Repositories\SectorRepository;
use Illuminate\Support\Collection;

readonly class SectorService
{
    public function __construct(private SectorRepository $sectorRepository)
    {
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->sectorRepository->getAll();
    }

    public function createNewSector(): Sector
    {
        return new Sector();
    }

    public function create(Collection $request): Sector|Model
    {
        $sector = $this->createNewSector();
        $sector->name = $request->get('name');
        $sector->color = $request->get('color');
        return $this->sectorRepository->save($sector);
    }
}
