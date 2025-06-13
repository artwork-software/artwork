<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Room\Repositories\RoomCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class RoomCategoryService
{
    public function __construct(private RoomCategoryRepository $roomCategoryRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->roomCategoryRepository->getAll();
    }
}
