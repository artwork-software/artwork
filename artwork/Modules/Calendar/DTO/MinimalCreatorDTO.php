<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;

class MinimalCreatorDTO extends Data
{
    public function __construct(
        public int $id,
        public string $first_name,
        public string $last_name,
        public ?string $profile_photo_url = null,
    ) {
    }
}
