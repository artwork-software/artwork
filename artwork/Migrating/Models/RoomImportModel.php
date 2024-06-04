<?php

namespace Artwork\Migrating\Models;

class RoomImportModel
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $name,
        public readonly string $description,
    ) {
    }
}
