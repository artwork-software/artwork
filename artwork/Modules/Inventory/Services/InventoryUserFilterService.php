<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryUserFilter;
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

    /**
     * Gibt gefilterte Artikel für einen User zurück und berechnet die einsatzbereite Menge im Zeitraum.
     */
    public function getFilteredArticles(User $user, $startDate = null, $endDate = null)
    {
        // Hole den gespeicherten Filter des Users
        $filter = $this->filterRepo->getByUser($user);

        // Basis-Query + sinnvolles Eager Loading
        $query = InventoryArticle::query()
            ->with([
                'category:id,name',
                'subCategory:id,name',
                // Properties inkl. Pivot-Wert
                'properties' => fn($q) => $q->withPivot('value')->select('inventory_article_properties.id', 'name', 'type'),
            ]);

        if ($filter) {
            // Kategorien
            if (!empty($filter->category_ids)) {
                $query->whereIn('inventory_category_id', $filter->category_ids);
            }

            // Sub-Kategorien
            if (!empty($filter->sub_category_ids)) {
                $query->whereIn('inventory_sub_category_id', $filter->sub_category_ids);
            }

            // Property-Filter (Pivot über inventory_property_values.value)
            if (!empty($filter->property_filters) && is_array($filter->property_filters)) {
                foreach ($filter->property_filters as $propertyId => $rawValue) {
                    // UI kann { [id]: "val" } oder { [id]: { value: "val" } } liefern
                    $value = (is_array($rawValue) && array_key_exists('value', $rawValue))
                        ? $rawValue['value']
                        : $rawValue;

                    if ($value === '' || $value === null) {
                        continue;
                    }

                    // Mehrfachwerte erlauben
                    $values = is_array($value) ? array_values($value) : [(string)$value];

                    $query->whereHas('properties', function ($q) use ($propertyId, $values) {
                        $q->where('inventory_article_properties.id', (int)$propertyId)
                            // WICHTIG: direkt auf die Pivot-Tabelle referenzieren
                            ->whereIn('inventory_property_values.value', $values);
                    });
                }
            }
        }

        /**
         * WICHTIG:
         * Wir geben HIER den Builder zurück (nicht ->get()),
         * damit der Aufrufer selbst paginieren / weitere Relationen laden kann.
         *
         * Falls du für eine Ansicht zusätzlich Verfügbarkeiten brauchst,
         * berechne die NACH dem Paginieren/->get() separat (wie in deiner Availability-Funktion).
         */
        return $query;
    }


    public function getFilteredArticlesNew(User $user): Builder
    {
        $filter = $this->filterRepo->getByUser($user);

        $qb = InventoryArticle::query()
            // Basis: was die Availability-Geschichte später sowieso braucht
            ->with([
                'category:id,name',
                'subCategory:id,name',
                'properties' => fn($q) => $q->withPivot('value')->select('inventory_article_properties.id','name','type'),
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

                $qb->whereHas('properties', function ($q) use ($propertyId, $values) {
                    $q->where('inventory_article_properties.id', (int)$propertyId)
                        // Wichtig: direkt auf die Pivot-Tabelle referenzieren
                        ->whereIn('inventory_property_values.value', $values);
                });
            }
        }

        // Tags
        if (!empty($filter->tag_ids)) {
            $qb->whereHas('tags', function ($q) use ($filter) {
                $q->whereIn('inventory_tags.id', $filter->tag_ids);
            });
        }

        return $qb;
    }
}
