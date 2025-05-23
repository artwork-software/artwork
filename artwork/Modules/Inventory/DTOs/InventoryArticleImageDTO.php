<?php

namespace Artwork\Modules\Inventory\DTOs;

use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Illuminate\Support\Facades\Storage;

class InventoryArticleImageDTO
{
    public string $url;

    /**
     * Create a DTO from an InventoryArticleImage model.
     *
     * @param InventoryArticleImage $image
     * @return static
     */
    public static function fromModel(InventoryArticleImage $image): static
    {
        $dto = new static();
        $dto->url = $image->getAbsoluteImageUrl();

        return $dto;
    }

    /**
     * Create a collection of DTOs from a collection of InventoryArticleImage models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $images
     * @return array
     */
    public static function fromCollection($images): array
    {
        $urls = [];

        foreach ($images as $image) {
            $urls[] = $image->getAbsoluteImageUrl();
        }

        return $urls;
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
