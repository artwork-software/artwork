<?php

namespace Artwork\Modules\CollectingSociety\Services;

use Artwork\Modules\CollectingSociety\Repositories\CollectingSocietyRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CollectingSocietyService
{
    public function __construct(private CollectingSocietyRepository $collectingSocietyRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->collectingSocietyRepository->getAll();
    }
}
