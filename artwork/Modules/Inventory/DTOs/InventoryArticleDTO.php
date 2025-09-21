<?php

namespace Artwork\Modules\Inventory\DTOs;

use Artwork\Modules\Inventory\Models\InventoryArticle;

class InventoryArticleDTO
{
    public int $id;
    public string $name;
    public ?string $description;
    public int $inventory_category_id;
    public ?int $inventory_sub_category_id;
    public int $quantity;
    public bool $is_detailed_quantity;
    public array $properties = [];
    public array $images = [];
    public array $room = [];
    public array $manufacturer = [];
    public string $created_at;
    public string $updated_at;

    /**
     * Create a DTO from an InventoryArticle model.
     *
     * @param InventoryArticle $article
     * @return static
     */
    public static function fromModel(InventoryArticle $article): static
    {
        $dto = new static();
        $dto->id = $article->id;
        $dto->name = $article->name;
        $dto->description = $article->description;
        $dto->inventory_category_id = $article->inventory_category_id;
        $dto->inventory_sub_category_id = $article->inventory_sub_category_id;
        $dto->quantity = $article->quantity;
        $dto->is_detailed_quantity = $article->is_detailed_quantity;
        $dto->properties = $article->properties->toArray();
        $dto->images = InventoryArticleImageDTO::fromCollection($article->images);
        $dto->room = $article->room;
        $dto->manufacturer = $article->manufacturer;
        $dto->created_at = $article->created_at;
        $dto->updated_at = $article->updated_at;

        return $dto;
    }

    /**
     * Create a collection of DTOs from a collection of InventoryArticle models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $articles
     * @return array
     */
    public static function fromCollection($articles): array
    {
        $dtos = [];

        foreach ($articles as $article) {
            $dtos[] = self::fromModel($article);
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
