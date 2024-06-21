<?php

namespace Artwork\Modules\Category\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Category\Repositories\CategoryRepository;
use Illuminate\Support\Collection;

readonly class CategoryService
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->categoryRepository->getAll();
    }

    public function create(Collection $request): Category|Model
    {
        $category = new Category();
        $category->name = $request->get('name');
        $category->color = $request->get('color');
        return $this->categoryRepository->save($category);
    }
}
