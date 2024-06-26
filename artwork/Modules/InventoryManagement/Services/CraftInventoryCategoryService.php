<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryCategoryRepository;
use Throwable;

readonly class CraftInventoryCategoryService
{
    public function __construct(
        private CraftInventoryCategoryRepository $craftInventoryCategoryRepository
    ) {
    }

    public function getNewCraftInventoryCategory(array $attributes): CraftInventoryCategory
    {
        return new CraftInventoryCategory($attributes);
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $craftId,
        string $name,
        int $order
    ): CraftInventoryCategory {
        $craftsInventoryCategory = $this->getNewCraftInventoryCategory(
            [
                'craft_id' => $craftId,
                'name' => $name,
                'order' => $order
            ]
        );
        $this->craftInventoryCategoryRepository->saveOrFail($craftsInventoryCategory);

        return $craftsInventoryCategory;
    }

    /**
     * @throws Throwable
     */
    public function updateName(string $name, CraftInventoryCategory $craftInventoryCategory): void
    {
        $this->craftInventoryCategoryRepository->updateOrFail(
            $craftInventoryCategory,
            [
                'name' => $name
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function updateOrder(CraftInventoryCategory $craftInventoryCategory, int $order): void
    {
    }

    public function forceDelete(int|CraftInventoryCategory $craftInventoryCategory): bool
    {
        if (!$craftInventoryCategory instanceof CraftInventoryCategory) {
            $craftInventoryCategory = $this->craftInventoryCategoryRepository->find($craftInventoryCategory);
        }

        return $this->craftInventoryCategoryRepository->forceDelete($craftInventoryCategory);
    }
}
