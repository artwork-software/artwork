<?php

namespace Artwork\Core\Cache;

use Artwork\Core\Database\Models\Model;

interface ServiceWithArrayCache
{
    public function findByIdWithoutCache(int $id): ?Model;

    public function findByNameWithoutCache(string $name): ?Model;
}
