<?php

namespace Artwork\Modules\RoomAttribute\Services;

use Artwork\Modules\RoomAttribute\Repositories\RoomAttributeRepository;
use Illuminate\Database\Eloquent\Collection;

class RoomAttributeService
{
    public function __construct(private readonly RoomAttributeRepository $roomAttributeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->roomAttributeRepository->getAll();
    }
}
