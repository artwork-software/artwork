<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class CraftsInventoryColumnService
{
    public function __construct(
        private CraftsInventoryColumnRepository $craftsInventoryColumnRepository
    ) {
    }

    public function getAllOrdered(): Collection
    {
        return $this->craftsInventoryColumnRepository->getAllOrdered();
    }

    public function getNewCraftsInventoryColumn(array $attributes): CraftsInventoryColumn
    {
        return new CraftsInventoryColumn($attributes);
    }

    /**
     * @throws Throwable
     */
    public function create(
        string $name,
        CraftsInventoryColumnTypeEnum $type,
        array $typeOptions = [],
        string $background_color = ''
    ): CraftsInventoryColumn {
        $craftsInventoryColumn = $this->getNewCraftsInventoryColumn([
            'name' => $name,
            'type' => $type,
            'type_options' => $typeOptions,
            'background_color' => $background_color
        ]);
        $this->craftsInventoryColumnRepository->saveOrFail($craftsInventoryColumn);

        return $craftsInventoryColumn;
    }

    /**
     * @throws Throwable
     */
    public function duplicate(int $columnId): CraftsInventoryColumn
    {
        /** @var CraftsInventoryColumn $duplicatedColumn */
        $duplicatedColumn = $this->craftsInventoryColumnRepository->saveOrFail(
            $this->craftsInventoryColumnRepository->replicate(
                $this->craftsInventoryColumnRepository->find($columnId)
            )
        );

        return $duplicatedColumn;
    }

    /**
     * @throws Throwable
     */
    public function updateName(string $name, CraftsInventoryColumn $craftsInventoryColumn): void
    {
        $this->craftsInventoryColumnRepository->updateOrFail(
            $craftsInventoryColumn,
            [
                'name' => $name
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function updateBackgroundColor(
        string $backgroundColor,
        CraftsInventoryColumn $craftsInventoryColumn
    ): void {
        $this->craftsInventoryColumnRepository->updateOrFail(
            $craftsInventoryColumn,
            [
                'background_color' => $backgroundColor
            ]
        );
    }

    public function forceDelete(int|CraftsInventoryColumn $craftsInventoryColumn): bool
    {
        if (!$craftsInventoryColumn instanceof CraftsInventoryColumn) {
            $craftsInventoryColumn = $this->craftsInventoryColumnRepository->find($craftsInventoryColumn);
        }

        return $this->craftsInventoryColumnRepository->forceDelete($craftsInventoryColumn);
    }
}