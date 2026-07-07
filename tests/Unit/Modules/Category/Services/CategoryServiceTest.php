<?php

namespace Tests\Unit\Modules\Category\Services;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Category\Services\CategoryService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CategoryServiceTest extends TestCase
{
    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CategoryService::class);
    }

    #[Test]
    public function get_all_returns_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertCount(3, $result);
    }

    #[Test]
    public function create_new_category_returns_unsaved_instance(): void
    {
        $category = $this->service->createNewCategory();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertFalse($category->exists);
    }

    #[Test]
    public function create_persists_category_with_name_and_color(): void
    {
        $payload = new Collection([
            'name' => 'Theater',
            'color' => '#0044ff',
        ]);

        $category = $this->service->create($payload);

        $this->assertTrue($category->exists);
        $this->assertSame('Theater', $category->name);
        $this->assertDatabaseHas('categories', ['name' => 'Theater', 'color' => '#0044ff']);
    }
}
