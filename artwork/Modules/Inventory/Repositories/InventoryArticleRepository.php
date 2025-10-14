<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        return $query->with(['properties', 'category', 'subCategory', 'images', 'detailedArticleQuantities.status', 'statusValues'])->paginate($perPage);
    }

    public function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            $property = InventoryArticleProperties::find($filter['property_id']);

            // Apply filter using OR logic: match either main article properties OR detailed quantities properties
            $query->where(function ($orQuery) use ($filter, $property) {
                // Check main article properties
                $orQuery->whereHas('properties', function ($q) use ($filter, $property) {
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

                    if ($property && $property->type === 'manufacturer') {
                        $q->join('manufacturers', 'inventory_property_values.value', '=', 'manufacturers.id');

                        match ($filter['operator']) {
                            'like' => $q->where('manufacturers.name', 'like', '%' . $filter['value'] . '%'),
                            'starts_with' => $q->where('manufacturers.name', 'like', $filter['value'] . '%'),
                            'ends_with' => $q->where('manufacturers.name', 'like', '%' . $filter['value']),
                            'exact', 'equals' => $q->where('manufacturers.name', '=', $filter['value']),
                            'not_equals' => $q->where('manufacturers.name', '!=', $filter['value']),
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

                // Also check detailed article quantities properties for articles with is_detailed_quantity = true
                $orQuery->orWhere(function ($detailedQuery) use ($filter, $property) {
                    $detailedQuery->where('is_detailed_quantity', true)
                        ->whereHas('detailedArticleQuantities.properties', function ($q) use ($filter, $property) {
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

                            if ($property && $property->type === 'manufacturer') {
                                $q->join('manufacturers', 'inventory_property_values.value', '=', 'manufacturers.id');

                                match ($filter['operator']) {
                                    'like' => $q->where('manufacturers.name', 'like', '%' . $filter['value'] . '%'),
                                    'starts_with' => $q->where('manufacturers.name', 'like', $filter['value'] . '%'),
                                    'ends_with' => $q->where('manufacturers.name', 'like', '%' . $filter['value']),
                                    'exact', 'equals' => $q->where('manufacturers.name', '=', $filter['value']),
                                    'not_equals' => $q->where('manufacturers.name', '!=', $filter['value']),
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
                });
            });
        }

        return $query;
    }

    public function create(array $data): InventoryArticle
    {
        return InventoryArticle::create($data);
    }

    public function addImages(InventoryArticle $article, array $images, ?int $mainImageIndex = null): void
    {
        foreach ($images as $index => $image) {
            $created = $article->images()->create([
                'image' => $image->store('inventory_articles', 'public'),
                'is_main_image' => false,
                'order' => 0
            ]);

            if ($index === $mainImageIndex) {
                $created->update(['is_main_image' => true]);
            }
        }
    }

    public function attachProperties(InventoryArticle $article, Collection $properties): void
    {
        foreach ($properties as $property) {
            $article->properties()->attach((int)$property['id'], [
                'value' => (string)$property['value']
            ]);
        }
    }

    public function attachStatusValues(InventoryArticle $article, array $statusValues): void
    {
        foreach ($statusValues as $statusValue) {
            $article->statusValues()->attach((int)$statusValue['id'], [
                'value' => (string)$statusValue['value']
            ]);
        }
    }

    public function detachAllStatusValues(InventoryArticle $article): void
    {
        $article->statusValues()->detach();
    }

    public function addDetailedArticles(InventoryArticle $article, Collection $detailedArticles): void
    {
        foreach ($detailedArticles as $detailedArticleData) {
            $detailedArticle = $article->detailedArticleQuantities()->create([
                'name' => $detailedArticleData['name'],
                'quantity' => $detailedArticleData['quantity'],
                'description' => $detailedArticleData['description'],
                'inventory_article_status_id' => $detailedArticleData['status']['id'] ?? null,
                'type_number' => Str::uuid()->toString(),
            ]);

            if(array_key_exists('properties', $detailedArticleData)) {
                foreach ($detailedArticleData['properties'] as $property) {
                    $detailedArticle->properties()->attach((int)$property['id'], [
                        'value' => (string)$property['value']
                    ]);
                }
            }
        }
    }

    public function update(InventoryArticle $article, array $data): void
    {
        $article->update($data);
    }

    public function detachAllProperties(InventoryArticle $article): void
    {
        $article->properties()->detach();
    }

    public function detachAllDetailedArticleProperties(InventoryArticle $article): void
    {
        foreach ($article->detailedArticleQuantities as $detailedArticle) {
            $detailedArticle->properties()->detach();
        }
    }

    public function deleteAllDetailedArticles(InventoryArticle $article): void
    {
        $article->detailedArticleQuantities()->delete();
    }


    public function delete(InventoryArticle $article): void
    {
        $article->images()->delete();
        $article->detailedArticleQuantities()->delete();
        $article->statusValues()->detach();
        $article->delete();
    }

    public function getAllTrashed(): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryArticle::onlyTrashed()
            ->with([
                'properties',
                'category',
                'subCategory',
                'images' => function ($query) {
                    $query->withTrashed();
                },
                'detailedArticleQuantities' => function ($query) {
                    $query->withTrashed();
                },
            ])
            ->get();
    }

    public function forceDelete(InventoryArticle $article): void
    {
        $images = $article->images()->withTrashed()->get();
        // delete images on Storage in public inventory_articles
        foreach ($images as $image) {
            if ($image->image && Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
        }

        // delete images
        $images->forceDelete();

        // delete detailed articles
        $detailedArticles = $article->detailedArticleQuantities()->withTrashed()->get();
        foreach ($detailedArticles as $detailedArticle) {
            $detailedArticle->properties()->detach();
        }


        // delete detailed articles
        $detailedArticles->forceDelete();

        // delete article
        $article->properties()->detach();
        $article->forceDelete();
    }

    public function restore(InventoryArticle $article): void
    {
        $images = $article->images()->withTrashed()->get();
        foreach ($images as $image) {
            $image->restore();
        }

        $detailedArticles = $article->detailedArticleQuantities()->withTrashed()->get();

        foreach ($detailedArticles as $detailedArticle) {
            $detailedArticle->restore();
        }

        $article->restore();
    }

    public function getAvailableStock(InventoryArticle $article, string $startDate, string $endDate): array
    {
        // Get all available stock for the given article
        // get article stock for the given date range in all issues and returns the available stock of the article
        return $article->getAvailableStock($startDate, $endDate);

    }

    public function getAllWithCategories(): \Illuminate\Support\Collection
    {
        return InventoryArticle::with(['category', 'subCategory'])->get()->keyBy('id');
    }
}
