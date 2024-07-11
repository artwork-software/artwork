<?php

namespace Artwork\Modules\RoomCategory\Services;

use Artwork\Modules\RoomCategory\Repositories\RoomCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class RoomCategoryService
{
    public function __construct(private readonly RoomCategoryRepository $roomCategoryRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->roomCategoryRepository->getAll();
    }
}
