<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;

class ApiDto
{
    public function __construct(
        public string $name,
        public Carbon $date,
        public Subdivision $subdivision,
        public ?int $rota = 0,
        public ?string $remoteIdentifier = null,
        public ?bool $fromApi = false
    )
    {
    }
}
