<?php

namespace Artwork\Migrating\Models;

class EventTypeImportModel
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
    ) {
    }
}
