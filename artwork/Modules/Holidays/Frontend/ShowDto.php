<?php

namespace Artwork\Modules\Holidays\Frontend;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;

class ShowDto
{
    public function __construct(
        public readonly Holiday $holiday,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->holiday->id,
            'name' => $this->holiday->name,
            'date' => $this->holiday->date->translatedFormat('l, jS F Y'),
            'end_date' => $this->holiday->date->translatedFormat('l, jS F Y'),
            'subdivisions' => $this->holiday->subdivisions,
            'rota' => $this->holiday->rota,
            'from_api' => $this->holiday->from_api,
        ];
    }
}
