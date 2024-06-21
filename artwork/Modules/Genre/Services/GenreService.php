<?php

namespace Artwork\Modules\Genre\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Genre\Repositories\GenreRepository;
use Illuminate\Support\Collection;

readonly class GenreService
{
    public function __construct(private GenreRepository $genreRepository)
    {
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->genreRepository->getAll();
    }

    public function create(Collection $request): Genre|Model
    {
        $genre = new Genre();
        $genre->name = $request->get('name');
        $genre->color = $request->get('color');
        return $this->genreRepository->save($genre);
    }
}
