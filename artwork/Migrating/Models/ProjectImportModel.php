<?php

namespace Artwork\Migrating\Models;

use Carbon\Carbon;

class ProjectImportModel
{
    public Carbon|null $end;

    public Carbon|null $start;

    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly string $description,
        public readonly string $projectGroupIdentifier,
        ?string $start,
        ?string $end,
    ) {
        if ($start) {
            $this->start = Carbon::parse($start);
        }
        if ($end) {
            $this->end = Carbon::parse($end);
        }
    }
}
