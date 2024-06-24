<?php

namespace Artwork\Modules\Category\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Category\Models\Category;

readonly class CategoryRepository extends BaseRepository
{
    public function getAll()
    {
        return Category::all();
    }
}
