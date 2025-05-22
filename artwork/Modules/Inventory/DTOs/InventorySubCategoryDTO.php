<?php

namespace Artwork\Modules\Inventory\DTOs;

use Artwork\Modules\Inventory\Models\InventorySubCategory;

class InventorySubCategoryDTO
{
    public int $id;
    public string $name;
    public ?string $description;
    public int $inventory_category_id;
    public array $properties = [];
    public array $articles = [];

    /**
     * Create a DTO from an InventorySubCategory model.
     *
     * @param InventorySubCategory $subCategory
     * @return static
     */
    public static function fromModel(InventorySubCategory $subCategory): static
    {
        $dto = new static();
        $dto->id = $subCategory->id;
        $dto->name = $subCategory->name;
        $dto->description = $subCategory->description;
        $dto->inventory_category_id = $subCategory->inventory_category_id;
        $dto->properties = $subCategory->properties->toArray();
        $dto->articles = InventoryArticleDTO::fromCollection($subCategory->articles);

        return $dto;
    }

    /**
     * Create a collection of DTOs from a collection of InventorySubCategory models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $subCategories
     * @return array
     */
    public static function fromCollection($subCategories): array
    {
        $dtos = [];

        foreach ($subCategories as $subCategory) {
            $dtos[] = self::fromModel($subCategory);
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
