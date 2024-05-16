<?php

namespace Artwork\Modules\RoomAttribute\Services;

use Artwork\Modules\RoomAttribute\Repositories\RoomAttributeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class RoomAttributeService
{
    public function __construct(private RoomAttributeRepository $roomAttributeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->roomAttributeRepository->getAll();
    }
}
