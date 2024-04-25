<?php

namespace Artwork\Modules\Genre\Services;

use Artwork\Modules\Genre\Repositories\GenreRepository;

readonly class GenreService
{
    public function __construct(private GenreRepository $genreRepository)
    {
    }
}
