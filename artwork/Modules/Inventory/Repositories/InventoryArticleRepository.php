<?php

namespace Artwork\Modules\Inventory\Repositories;

use App\Jobs\GenerateInventoryArticleImageThumbnail;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class InventoryArticleRepository
{
    public function __construct(
        private readonly InventoryArticleImageService $imageService
    ) {
    }

    public function count(): int
    {
        return InventoryArticle::count();
    }

    public function search(string $term)
    {
        return InventoryArticle::search($term)->get();
    }

    /**
     * Ref 1.28: SQL-based search across all property values (incl. serial number),
     * name, inventory number and category. Supports wildcard syntax (`*158`) and an
     * optional scope to a single property. Used when Meilisearch cannot express the
     * query (wildcard or attribute-scoped search).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, InventoryArticle>
     */
    public function searchAdvanced(string $term, ?int $propertyId = null)
    {
        // Translate wildcard `*` to SQL `%`; a plain term matches as substring.
        $pattern = str_contains($term, '*')
            ? str_replace('*', '%', $term)
            : '%' . $term . '%';

        return InventoryArticle::query()
            ->withoutTrashed()
            ->where(function ($q) use ($pattern, $propertyId): void {
                // Main article property values
                $q->whereHas('properties', function ($pq) use ($pattern, $propertyId): void {
                    $pq->where('inventory_property_values.value', 'like', $pattern);
                    if ($propertyId) {
                        $pq->where('inventory_article_properties.id', $propertyId);
                    }
                });

                // Detailed article (single inventory) property values
                $q->orWhereHas('detailedArticleQuantities.properties', function ($pq) use ($pattern, $propertyId): void {
                    $pq->where('inventory_property_values.value', 'like', $pattern);
                    if ($propertyId) {
                        $pq->where('inventory_article_properties.id', $propertyId);
                    }
                });

                // When not scoped to a single property, also match the basics.
                if (!$propertyId) {
                    $q->orWhere('inventory_articles.name', 'like', $pattern)
                        ->orWhere('inventory_articles.inventory_number', 'like', $pattern)
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $pattern))
                        ->orWhereHas('subCategory', fn ($c) => $c->where('name', 'like', $pattern));
                }
            })
            ->get(['inventory_articles.id']);
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
                            default => null,
                        };

                        return;
                    }

                    if ($property && $property->type === 'manufacturer') {
                        $q->join('crm_contacts', 'inventory_property_values.value', '=', 'crm_contacts.id');

                        match ($filter['operator']) {
                            'like' => $q->where('crm_contacts.display_name', 'like', '%' . $filter['value'] . '%'),
                            'starts_with' => $q->where('crm_contacts.display_name', 'like', $filter['value'] . '%'),
                            'ends_with' => $q->where('crm_contacts.display_name', 'like', '%' . $filter['value']),
                            'exact', 'equals' => $q->where('crm_contacts.display_name', '=', $filter['value']),
                            'not_equals' => $q->where('crm_contacts.display_name', '!=', $filter['value']),
                            default => null,
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
                                    default => null,
                                };

                                return;
                            }

                            if ($property && $property->type === 'manufacturer') {
                                $q->join('crm_contacts', 'inventory_property_values.value', '=', 'crm_contacts.id');

                                match ($filter['operator']) {
                                    'like' => $q->where('crm_contacts.display_name', 'like', '%' . $filter['value'] . '%'),
                                    'starts_with' => $q->where('crm_contacts.display_name', 'like', $filter['value'] . '%'),
                                    'ends_with' => $q->where('crm_contacts.display_name', 'like', '%' . $filter['value']),
                                    'exact', 'equals' => $q->where('crm_contacts.display_name', '=', $filter['value']),
                                    'not_equals' => $q->where('crm_contacts.display_name', '!=', $filter['value']),
                                    default => null,
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
        // A new main image replaces the previous one — otherwise two images
        // end up flagged and the sorting lets the old one win.
        if ($mainImageIndex !== null) {
            $article->images()->update(['is_main_image' => false]);
        }

        foreach ($images as $index => $image) {
            $created = $article->images()->create($this->imageService->store($image) + [
                'is_main_image' => false,
                'order' => 0
            ]);

            if ($index === $mainImageIndex) {
                $created->update(['is_main_image' => true]);
            }

            GenerateInventoryArticleImageThumbnail::dispatch($created)->afterCommit();
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

    public function update(InventoryArticle $article, array $data): void
    {
        $article->update($data);
    }

    public function detachAllProperties(InventoryArticle $article): void
    {
        $article->properties()->detach();
    }


    public function delete(InventoryArticle $article): void
    {
        $article->images()->delete();
        $article->detailedArticleQuantities()->delete();
        // Keep status value pivots: this is only a soft delete — detaching here
        // would irreversibly wipe the stock quantities and restore() would
        // bring the article back with stock 0. forceDelete() cleans the
        // pivots via FK ON DELETE CASCADE.
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
            if ($image->thumbnail && Storage::disk('public')->exists($image->thumbnail)) {
                Storage::disk('public')->delete($image->thumbnail);
            }
            // permanently delete image record
            $image->forceDelete();
        }

        $detailedArticles = $article->detailedArticleQuantities()->withTrashed()->get();

        // remove uploaded files of "file" type properties (article + detailed articles)
        // before the pivot rows are detached, so the paths are still resolvable.
        $this->deletePropertyFiles($article, $detailedArticles);

        // delete detailed articles
        foreach ($detailedArticles as $detailedArticle) {
            $detailedArticle->properties()->detach();
            $detailedArticle->forceDelete();
        }

        // delete article
        $article->properties()->detach();
        $article->forceDelete();
    }

    /**
     * Permanently delete the stored files of "file" type property values that
     * belong to the given article and its detailed articles. A file is only
     * removed when no other (kept) property value still references the same
     * path – this protects shared paths created by article duplication.
     *
     * @param \Illuminate\Support\Collection<int, InventoryDetailedQuantityArticle> $detailedArticles
     */
    private function deletePropertyFiles(InventoryArticle $article, Collection $detailedArticles): void
    {
        $values = InventoryPropertyValue::query()
            ->whereHas('property', fn ($query) => $query->where('type', 'file'))
            ->where(function ($query) use ($article, $detailedArticles) {
                $query->where(function ($q) use ($article) {
                    $q->where('inventory_propertyable_type', InventoryArticle::class)
                        ->where('inventory_propertyable_id', $article->id);
                });

                if ($detailedArticles->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($detailedArticles) {
                        $q->where('inventory_propertyable_type', InventoryDetailedQuantityArticle::class)
                            ->whereIn('inventory_propertyable_id', $detailedArticles->pluck('id')->all());
                    });
                }
            })
            ->get(['id', 'value']);

        $removedIds = $values->pluck('id')->all();

        foreach ($values->pluck('value')->filter()->unique() as $path) {
            // Only ever touch files inside the dedicated property uploads directory.
            if (!str_starts_with((string) $path, 'uploads/inventory-properties/')) {
                continue;
            }

            // Skip if another, non-removed property value still points to this file.
            $stillReferenced = InventoryPropertyValue::query()
                ->where('value', $path)
                ->whereNotIn('id', $removedIds)
                ->exists();

            if ($stillReferenced) {
                continue;
            }

            if (Storage::exists($path)) {
                Storage::delete($path);
                Storage::deleteDirectory(dirname($path));
            }
        }
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
