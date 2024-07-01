<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemEventServices;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Illuminate\Database\Eloquent\Collection;

readonly class CraftService
{
    public function __construct(private CraftRepository $craftRepository)
    {
    }

    public function getAll(array $with = []): Collection
    {
        return $this->craftRepository->getAll($with);
    }

    public function getAllWithInventoryCategoriesRelations(): Collection
    {
        return $this->getAll([
            'inventoryCategories',
            'inventoryCategories.groups',
            'inventoryCategories.groups.items',
            'inventoryCategories.groups.items.cells',
            'inventoryCategories.groups.items.cells.column'
        ]);
    }

    public function storeByRequest(CraftStoreRequest $craftStoreRequest): void
    {
        $craft = new Craft();
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
        }
    }

    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest
            ->only(['name', 'abbreviation', 'assignable_by_all', 'color', 'notify_days']));
        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
        } else {
            $this->craftRepository->detachUsers($craft);
        }
    }

    public function delete(Craft $craft): void
    {
        $this->craftRepository->detachUsers($craft);
        $this->craftRepository->delete($craft);
    }

    public function getAssignableByAllCrafts(): Collection
    {
        return $this->craftRepository->getAssignableByAllCrafts();
    }

    public function findById(int $id): Craft
    {
        return $this->craftRepository->findById($id);
    }

    public function getCraftsWithInventory(
        CraftInventoryItemService $craftInventoryItemService,
        CraftInventoryItemEventServices $craftInventoryItemEventServices
    ): \Illuminate\Support\Collection {
        // Eager load the necessary relationships
        return $this->craftRepository->getAll([
            'inventoryCategories.groups.items.events.user',
            'inventoryCategories.groups.items.cells',
            'inventoryCategories.groups.items.events.event.project',
        ])->map(function (Craft $craft) use ($craftInventoryItemEventServices, $craftInventoryItemService) {
            return [
                'id' => $craft->id,
                'name' => $craft->name,
                'inventory_categories' =>
                    $craft->inventoryCategories->map(function (CraftInventoryCategory $category)
 use ($craftInventoryItemEventServices, $craftInventoryItemService) {
                        return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'groups' => $category->groups->map(function (CraftInventoryGroup $group)
 use ($craftInventoryItemEventServices, $craftInventoryItemService) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'items' => $group->items->map(function (CraftInventoryItem $item)
 use ($craftInventoryItemEventServices, $craftInventoryItemService) {
                                    return [
                                        'id' => $item->id,
                                        'name' => $craftInventoryItemService->getItemName($item),
                                        'count' => $craftInventoryItemService->getItemCount($item),
                                        'events' => $craftInventoryItemEventServices->getItemEvents($item),
                                    ];
                                }),
                            ];
                        }),
                        ];
                    }),
            ];
        });
    }
}
