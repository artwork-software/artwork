<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryUserFilter;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryUserFilterRepository;
use Artwork\Modules\User\Models\User;

class InventoryUserFilterService
{
    protected InventoryUserFilterRepository $filterRepo;

    public function __construct(InventoryUserFilterRepository $filterRepo)
    {
        $this->filterRepo = $filterRepo;
    }

    /**
     * Gibt gefilterte Artikel für einen User zurück.
     */
    public function getFilteredArticles(User $user)
    {
        $filter = $this->filterRepo->getByUser($user);
        if (!$filter) {
            return InventoryArticle::query();
        }

        $query = InventoryArticle::query();

        if (!empty($filter->category_ids)) {
            $query->whereIn('inventory_category_id', $filter->category_ids);
        }
        if (!empty($filter->sub_category_ids)) {
            $query->whereIn('inventory_sub_category_id', $filter->sub_category_ids);
        }
        if (!empty($filter->property_filters)) {
            foreach ($filter->property_filters as $propertyId => $value) {
                $query->whereHas('properties', function ($q) use ($propertyId, $value) {
                    $q->where('inventory_article_properties.id', $propertyId)
                      ->where('inventory_article_properties_pivot.value', $value);
                });
            }
        }
        return $query;
    }
}
