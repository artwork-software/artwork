<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class RoomDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $has_events = false,
        public array|null|Optional $admins
    ) {
    }
}