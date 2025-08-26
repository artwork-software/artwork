<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Repositories\InventoryUserFilterRepository;
use Artwork\Modules\User\Models\User;

class InventoryUserFilterShareService
{
    protected InventoryUserFilterRepository $filterRepo;

    public function __construct(InventoryUserFilterRepository $filterRepo)
    {
        $this->filterRepo = $filterRepo;
    }

    /**
     * Gibt alle Filtermöglichkeiten und User-Filter für Inertia:share zurück
     */
    public function getFilterDataForUser(User $user): array
    {
        $categories = InventoryCategory::select('id', 'name')->with([
            'subCategories:id,name,inventory_category_id'
        ])->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'sub_categories' => $cat->subCategories->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'inventory_category_id' => $sub->inventory_category_id,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $filterableProperties = InventoryArticleProperties::filterable()->select('id', 'name', 'type', 'select_values')->get()->map(function ($prop) {
            return [
                'id' => $prop->id,
                'name' => $prop->name,
                'type' => $prop->type,
                'select_values' => $prop->select_values ?? [],
            ];
        })->toArray();

        $userFilter = $this->filterRepo->getByUser($user);
        $userFilterArr = [
            'category_ids' => $userFilter?->category_ids ?? [],
            'sub_category_ids' => $userFilter?->sub_category_ids ?? [],
            'property_filters' => $userFilter?->property_filters ?? [],
        ];

        return [
            'categories' => $categories,
            'filterable_properties' => $filterableProperties,
            'user_filter' => $userFilterArr,
        ];
    }
}
