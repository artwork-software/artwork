<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryUserFilterRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class InventoryUserFilterService
{
    protected InventoryUserFilterRepository $filterRepo;

    public function __construct(InventoryUserFilterRepository $filterRepo)
    {
        $this->filterRepo = $filterRepo;
    }


    public function getFilteredArticlesNew(User $user): Builder
    {
        $filter = $this->filterRepo->getByUser($user);

        $qb = InventoryArticle::query()
            // Basis: was die Availability-Geschichte später sowieso braucht
            ->with([
                'category:id,name',
                'subCategory:id,name',
                'properties' => fn($q) => $q->withPivot('value')->select(
                    'inventory_article_properties.id',
                    'name',
                    'type'
                ),
            ]);

        if (!$filter) {
            return $qb;
        }

        // Kategorien
        if (!empty($filter->category_ids)) {
            $qb->whereIn('inventory_category_id', $filter->category_ids);
        }

        // Sub-Kategorien
        if (!empty($filter->sub_category_ids)) {
            $qb->whereIn('inventory_sub_category_id', $filter->sub_category_ids);
        }

        if (!empty($filter->property_filters) && is_array($filter->property_filters)) {
            foreach ($filter->property_filters as $propertyId => $raw) {
                // UI kann { [id]: "val" } oder { [id]: { value: "val" } } liefern
                $value  = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
                $values = is_array($value) ? array_values($value) : [(string)$value];

                $qb->whereHas('properties', function ($q) use ($propertyId, $values): void {
                    $q->where('inventory_article_properties.id', (int)$propertyId)
                        // Wichtig: direkt auf die Pivot-Tabelle referenzieren
                        ->whereIn('inventory_property_values.value', $values);
                });
            }
        }

        // Tags
        if (!empty($filter->tag_ids)) {
            $qb->whereHas('tags', function ($q) use ($filter): void {
                $q->whereIn('inventory_tags.id', $filter->tag_ids);
            });
        }

        return $qb;
    }
}
