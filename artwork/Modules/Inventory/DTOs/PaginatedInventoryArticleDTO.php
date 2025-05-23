<?php

namespace Artwork\Modules\Inventory\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatedInventoryArticleDTO
{
    public array $data = [];
    public array $meta = [];
    public array $links = [];

    public static function fromPaginator(LengthAwarePaginator $paginator): static
    {
        $dto = new static();

        $dto->data = InventoryArticleDTO::fromCollection($paginator->getCollection());

        $paginationArray = $paginator->toArray();
        unset($paginationArray['data']); // Remove the data as we've already transformed it

        if (isset($paginationArray['meta'])) {
            $dto->meta = $paginationArray['meta'];
        } else {
            $dto->meta = [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ];
        }

        if (isset($paginationArray['links'])) {
            $dto->links = $paginationArray['links'];
        }

        return $dto;
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
