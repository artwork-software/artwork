<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;

class InventoryArticleRepository
{
    public function count(): int
    {
        return InventoryArticle::count();
    }

    public function search(string $term)
    {
        return InventoryArticle::search($term)->get();
    }

    public function baseQuery()
    {
        return InventoryArticle::query();
    }

    public function withRelations($query, int $perPage = 50)
    {
        return $query->with(['properties', 'category', 'subCategory', 'images'])->paginate($perPage);
    }

    public function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            $property = InventoryArticleProperties::find($filter['property_id']);

            $query->whereHas('properties', function ($q) use ($filter, $property) {
                $q->where('inventory_article_properties.id', $filter['property_id']);

                if ($property && $property->type === 'room') {
                    $q->join('rooms', 'inventory_property_values.value', '=', 'rooms.id');

                    match ($filter['operator']) {
                        'like' => $q->where('rooms.name', 'like', '%' . $filter['value'] . '%'),
                        'starts_with' => $q->where('rooms.name', 'like', $filter['value'] . '%'),
                        'ends_with' => $q->where('rooms.name', 'like', '%' . $filter['value']),
                        'exact', 'equals' => $q->where('rooms.name', '=', $filter['value']),
                        'not_equals' => $q->where('rooms.name', '!=', $filter['value']),
                    };

                    return;
                }

                $q->where(function ($subQuery) use ($filter) {
                    $column = 'inventory_property_values.value';

                    match ($filter['operator']) {
                        'like' => $subQuery->where($column, 'like', '%' . $filter['value'] . '%'),
                        'starts_with' => $subQuery->where($column, 'like', $filter['value'] . '%'),
                        'ends_with' => $subQuery->where($column, 'like', '%' . $filter['value']),
                        'exact', 'equals' => $subQuery->where($column, '=', $filter['value']),
                        'not_equals' => $subQuery->where($column, '!=', $filter['value']),
                        'less_than' => $subQuery->where($column, '<', $filter['value']),
                        'greater_than' => $subQuery->where($column, '>', $filter['value']),
                        'is_null' => $subQuery->whereNull($column),
                        'not_like' => $subQuery->where($column, 'not like', '%' . $filter['value'] . '%'),
                        'date_before' => $subQuery->whereDate($column, '<', $filter['value']),
                        'date_after' => $subQuery->whereDate($column, '>', $filter['value']),
                        'from' => $subQuery->where($column, '>=', $filter['value']),
                        'until' => $subQuery->where($column, '<=', $filter['value']),
                        default => null,
                    };
                });
            });
        }

        return $query;
    }
}