<?php

namespace Artwork\Modules\Category\Services;

use Artwork\Modules\Category\Repositories\CategoryRepository;

readonly class CategoryService
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function getAll()
    {
        return $this->categoryRepository->getAll();
    }
}
