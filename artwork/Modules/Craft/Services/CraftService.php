<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class CraftService
{
    public function __construct(
        private readonly CraftRepository $craftRepository,
        private readonly CraftInventoryItemCellService $craftInventoryItemCellService,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService
    ) {
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
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all', 'universally_applicable']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
        }
    }

    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest
            ->only(['name', 'abbreviation', 'assignable_by_all', 'color', 'notify_days', 'universally_applicable']));
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

    public function getCraftsWithInventory(?Carbon $startDate = null, ?Carbon $endDate = null): SupportCollection
    {
        // Eager load the necessary relationships
        return $this->craftRepository->getAll([
            'inventoryCategories',
            'inventoryCategories.groups',
            'inventoryCategories.groups.items',
            'inventoryCategories.groups.items.events'  => function (HasMany $query) use ($startDate, $endDate): void {
                if ($startDate && $endDate) {
                    $query->whereBetween('start', [$startDate, $endDate])
                        ->orWhereBetween('end', [$startDate, $endDate]);
                }
                $query->with([
                    'user',
                    'event',
                    'event.project',
                ]);
            },
            'inventoryCategories.groups.items.events.user',
            'inventoryCategories.groups.items.events.event',
            'inventoryCategories.groups.items.events.event.project',
            'inventoryCategories.groups.items.cells',
            'inventoryCategories.groups.items.cells.column',
        ])->map(function (Craft $craft): array {
            return [
                'id' => $craft->id,
                'name' => $craft->name,
                'inventory_categories' => $craft->inventoryCategories->map(
                    function (CraftInventoryCategory $category): array {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'groups' => $category->groups->map(
                                function (CraftInventoryGroup $group): array {
                                    return [
                                        'id' => $group->id,
                                        'name' => $group->name,
                                        'items' => $group->items->map(
                                            function (CraftInventoryItem $item): array {
                                                return [
                                                    'id' => $item->id,
                                                    'name' => $this->craftInventoryItemCellService
                                                        ->getNameForSchedulingFromCells($item->cells),
                                                    'count' => $this->craftInventoryItemCellService
                                                        ->getItemCountForSchedulingFromCells($item->cells),
                                                    'events' => $this->craftInventoryItemEventService
                                                        ->getItemEvents($item),
                                                    'cells' => $item->cells
                                                ];
                                            }
                                        ),
                                    ];
                                }
                            ),
                        ];
                    }
                ),
            ];
        });
    }

    public function reorder(array $crafts): void
    {
        foreach ($crafts as $craft) {
            $this->craftRepository->findById($craft['id'])->update(['position' => $craft['position']]);
        }
    }
}
