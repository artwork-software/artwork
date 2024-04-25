<?php

namespace Artwork\Modules\CollectingSociety\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Illuminate\Database\Eloquent\Collection;

readonly class CollectingSocietyRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return CollectingSociety::all();
    }
}
