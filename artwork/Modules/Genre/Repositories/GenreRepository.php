<?php

namespace Artwork\Modules\Genre\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Genre\Models\Genre;

readonly class GenreRepository extends BaseRepository
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Genre::all();
    }
}
