<?php

namespace Artwork\Modules\Category\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Category\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::all();
    }
}
