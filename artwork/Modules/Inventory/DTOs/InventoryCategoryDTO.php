<?php

namespace Artwork\Modules\Inventory\DTOs;

use Artwork\Modules\Inventory\Models\InventoryCategory;

class InventoryCategoryDTO
{
    public int $id;
    public string $name;
    public ?string $description;
    public array $properties = [];
    public array $subcategories = [];
    public array $articles = [];

    /**
     * Create a DTO from an InventoryCategory model.
     *
     * @param InventoryCategory $category
     * @return static
     */
    public static function fromModel(InventoryCategory $category): static
    {
        $dto = new static();
        $dto->id = $category->id;
        $dto->name = $category->name;
        $dto->description = $category->description;
        $dto->properties = $category->properties->toArray();
        $dto->subcategories = InventorySubCategoryDTO::fromCollection($category->subCategories);
        $dto->articles = InventoryArticleDTO::fromCollection($category->articles);

        return $dto;
    }

    /**
     * Create a collection of DTOs from a collection of InventoryCategory models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @return array
     */
    public static function fromCollection($categories): array
    {
        $dtos = [];

        foreach ($categories as $category) {
            $dtos[] = self::fromModel($category);
        }

        return $dtos;
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Create a DTO from an array.
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $dto = new static();

        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->{$key} = $value;
            }
        }

        return $dto;
    }
}
