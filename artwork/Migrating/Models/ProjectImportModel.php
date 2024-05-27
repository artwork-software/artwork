<?php

namespace Artwork\Migrating\Models;

use Carbon\Carbon;

class ProjectImportModel
{
    public readonly Carbon|null $end;

    public readonly Carbon|null $start;

    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly string $description,
        ?string                $start,
        ?string                $end,
    )
    {
        if($start) {
            $this->start = Carbon::parse($start);
        }
        if($end) {
            $this->end = Carbon::parse($end);
        }
    }
}
