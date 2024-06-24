<?php

namespace Artwork\Migrating\Models;

use Carbon\Carbon;

class ProjectGroupImportModel
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly string $description,
        public ?string $start,
        public ?string $end,
    ) {
        if ($start) {
            $this->start = Carbon::parse($start);
        }
        if ($end) {
            $this->end = Carbon::parse($end);
        }
    }
}
