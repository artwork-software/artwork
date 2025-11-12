<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroupFolder;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class CraftService
{
    public function __construct(
        private readonly CraftRepository $craftRepository,
        private readonly CraftInventoryItemCellService $craftInventoryItemCellService,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService,
        private readonly AuthManager $authManager
    ) {
    }

    public function getAll(array $with = []): Collection
    {
        return $this->craftRepository->getAll($with);
    }

    public function getAllWithInventoryCategoriesRelations(): SupportCollection
    {
        $columnIdForSort = $this->authManager->user()->inventory_sort_column_id;
        $directionForSort = $this->authManager->user()->inventory_sort_direction;

        $allInventories = $this->getAll([
            'inventoryCategories',
            'inventoryCategories.groups',
            'inventoryCategories.groups.folders',
            'inventoryCategories.groups.items',
            'inventoryCategories.groups.items.cells.column',
            'inventoryCategories.groups.folders.items',
            'inventoryCategories.groups.folders.items.cells.column'
        ]);

        return $allInventories->map(function ($inventory) use ($columnIdForSort, $directionForSort) {
            $inventory->inventoryCategories = $inventory->inventoryCategories
                ->map(function ($category) use ($columnIdForSort, $directionForSort) {
                    $category->groups = $category->groups
                        ->map(function ($group) use ($columnIdForSort, $directionForSort) {
                            // Sortiere die Items im Group
                            $sortedItems = $this->sortItems($group->items, $columnIdForSort, $directionForSort);

                            // Sortiere die Items in den Folders des Groups
                            $group->folders = $group->folders
                                ->map(function ($folder) use ($columnIdForSort, $directionForSort) {
                                    $sortedFolderItems = $this
                                    ->sortItems($folder->items, $columnIdForSort, $directionForSort);
                                    return $folder->setRelation('items', $sortedFolderItems);
                                });

                            return $group->setRelation('items', $sortedItems);
                        });
                    return $category;
                });
            return $inventory;
        });
    }

    /**
     * Sortiert Items nach einer angegebenen Spalte und Richtung.
     *
     * @param Collection $items
     * @param int $columnIdForSort
     * @param string $directionForSort
     * @return Collection
     */
    private function sortItems(Collection $items, ?int $columnIdForSort, ?string $directionForSort): Collection
    {
        return $directionForSort === 'asc'
            ? $items->sortBy(function ($item) use ($columnIdForSort) {
                $cell = $item->cells->firstWhere('crafts_inventory_column_id', $columnIdForSort);
                return $cell ? $cell->cell_value : '';
            })->values()
            : $items->sortByDesc(function ($item) use ($columnIdForSort) {
                $cell = $item->cells->firstWhere('crafts_inventory_column_id', $columnIdForSort);
                return $cell ? $cell->cell_value : '';
            })->values();
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
            ->only([
                'name',
                'abbreviation',
                'assignable_by_all',
                'color',
                'notify_days',
                'universally_applicable',
                'inventory_planned_by_all'
            ]));

        $managersToBeAssigned = $craftUpdateRequest->collect('managersToBeAssigned')->groupBy(
            function ($managerToBeAssigned) {
                return $managerToBeAssigned['manager_type'];
            }
        );

        if ($craftUpdateRequest->has('qualifications')) {
            $craft->qualifications()->detach();
            $craft->qualifications()->sync($craftUpdateRequest->collect('qualifications')->pluck('id')->toArray());
        }

        if ($managersToBeAssigned->empty()) {
            $craft->managingUsers()->sync([]);
            $craft->managingFreelancers()->sync([]);
            $craft->managingServiceProviders()->sync([]);
        }

        foreach ($managersToBeAssigned as $managerType => $managers) {
            switch ($managerType) {
                case User::class:
                    $craft->managingUsers()->sync($managers->pluck('manager_id'));
                    break;
                case Freelancer::class:
                    $craft->managingFreelancers()->sync($managers->pluck('manager_id'));
                    break;
                case ServiceProvider::class:
                    $craft->managingServiceProviders()->sync($managers->pluck('manager_id'));
                    break;
            }
        }

        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
        } else {
            $this->craftRepository->detachUsers($craft);
        }

        if (!$craftUpdateRequest->boolean('inventory_planned_by_all')) {
            $this->craftRepository->syncUsersInventory($craft, $craftUpdateRequest->get('users_for_inventory'));
        } else {
            $this->craftRepository->detachUsersInventory($craft);
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
        // Eager load necessary relationships with conditional filtering for events
        $relationships = [
            'inventoryCategories',
            'inventoryCategories.groups',
            'inventoryCategories.groups.folders',
            'inventoryCategories.groups.folders.items',
            'inventoryCategories.groups.items',
            'inventoryCategories.groups.items.cells',
            'inventoryCategories.groups.items.cells.column',
            'inventoryCategories.groups.folders.items.cells',
            'inventoryCategories.groups.folders.items.cells.column',
            'inventoryCategories.groups.items.events' => function (HasMany $query) use ($startDate, $endDate): void {
                $this->addEventFilters($query, $startDate, $endDate);
            },
            'inventoryCategories.groups.folders.items.events'
            => function (HasMany $query) use ($startDate, $endDate): void {
                $this->addEventFilters($query, $startDate, $endDate);
            },
        ];

        return $this->craftRepository->getAll($relationships)->map(function (Craft $craft): array {
            return [
                'id' => $craft->id,
                'name' => $craft->name,
                'inventory_categories' => $craft->inventoryCategories
                    ->map(fn($category) => $this->transformCategory($category)),
                'inventory_planned_by_all' => $craft->inventory_planned_by_all,
                'inventory_planer_ids' => $craft->craftInventoryPlaner->pluck('id'),
            ];
        });
    }


    protected function addEventFilters(HasMany $query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween('start', [$startDate, $endDate])
                ->orWhereBetween('end', [$startDate, $endDate]);
        }
        $query->with(['user', 'event', 'event.project']);
    }


    /**
     * Transform the craft inventory category.
     *
     * @param CraftInventoryCategory $category
     * @return string[]
     */
    protected function transformCategory(CraftInventoryCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'groups' => $category->groups->map(fn($group) => $this->transformGroup($group)),
        ];
    }


    /**
     * Transform the craft inventory group.
     *
     * @param CraftInventoryGroup $group
     * @return string[]
     */
    protected function transformGroup(CraftInventoryGroup $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'items' => $group->items->map(fn($item) => $this->transformItem($item)),
            'folders' => $group->folders->map(fn($folder) => $this->transformFolder($folder)),
        ];
    }


    /**
     * Transform the craft inventory group folder.
     *
     * @param CraftInventoryGroupFolder $folder
     * @return string[]
     */
    protected function transformFolder(CraftInventoryGroupFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'items' => $folder->items->map(fn($item) => $this->transformItem($item)),
        ];
    }

    /**
     * Transform the craft inventory item.
     *
     * @param CraftInventoryItem $item
     * @return string[]
     */
    protected function transformItem(CraftInventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $this->craftInventoryItemCellService->getNameForSchedulingFromCells($item->cells),
            'count' => $this->craftInventoryItemCellService->getItemCountForSchedulingFromCells($item->cells),
            'events' => $this->craftInventoryItemEventService->getItemEvents($item),
            'cells' => $item->cells,
        ];
    }


    public function reorder(array $crafts): void
    {
        foreach ($crafts as $craft) {
            $this->craftRepository->findById($craft['id'])->update(['position' => $craft['position']]);
        }
    }
}
