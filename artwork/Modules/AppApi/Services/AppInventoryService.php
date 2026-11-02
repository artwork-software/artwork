<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Inventory for the crew on site: look an article up, see its stock per status, where it is and
 * whether it is free on a date. Viewing follows the web (any user while the module is on); the
 * list, search and availability are the web's own (InventoryArticleService, readyQuantity).
 */
class AppInventoryService
{
    /** File properties link to a web-only download; the app leaves them out. */
    private const HIDDEN_PROPERTY_TYPES = ['file', 'room'];

    public function __construct(
        private readonly InventoryArticleService $articleService,
        private readonly ModuleSettingsService $moduleSettingsService,
    ) {
    }

    /** Mirrors the routes' module gate, so the app never shows an Inventar that answers 403. */
    public function canViewInventory(User $user): bool
    {
        return $this->moduleSettingsService->isModuleVisible('inventory')
            || $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return InventoryCategory::query()
            ->with(['subCategories' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(static fn (InventoryCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'subcategories' => $category->subCategories
                    ->map(static fn (InventorySubCategory $sub): array => ['id' => $sub->id, 'name' => $sub->name])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * One page of the article list, in the web's order (category, subcategory, name).
     *
     * @return array{articles: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function articlePage(
        ?InventoryCategory $category,
        ?InventorySubCategory $subCategory,
        string $search,
        int $perPage,
    ): array {
        $page = $this->articleService->getArticleList($category, $subCategory, $search, perPage: $perPage);
        /** @var Collection<int, InventoryArticle> $articles */
        $articles = collect($page->items());
        InventoryArticle::preloadPropertyLookups($articles);

        return [
            'articles' => $articles
                ->map(fn (InventoryArticle $article): array => $this->summary($article))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function article(InventoryArticle $article): array
    {
        $article->load([
            'properties',
            'images' => fn ($query) => $query->orderByDesc('is_main_image')->orderBy('order')->orderBy('id'),
            'statusValues',
            'detailedArticleQuantities' => fn ($query) => $query->orderBy('detail_number')->orderBy('id'),
            'detailedArticleQuantities.status',
            'detailedArticleQuantities.properties',
        ]);
        InventoryArticle::preloadPropertyLookupsDeep($article);

        return [
            ...$this->summary($article),
            'description' => $article->description ?: null,
            'images' => $article->images
                ->map(fn (InventoryArticleImage $image): array => [
                    'id' => $image->id,
                    'url' => $this->publicUrl($image->image),
                    'thumbnail_url' => $image->thumbnail ? $this->publicUrl($image->thumbnail) : null,
                ])
                ->values()
                ->all(),
            'statuses' => $this->statuses($article),
            'properties' => $this->properties($article),
            'units' => $article->is_detailed_quantity
                ? $article->detailedArticleQuantities
                    ->map(fn (InventoryDetailedQuantityArticle $unit): array => $this->unit($unit))
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * What is free in a window: the web's availability (ready stock minus the peak of
     * overlapping internal and external issues).
     *
     * @return array{available: float, reserved: float, total: float}
     */
    public function availability(InventoryArticle $article, string $start, string $end): array
    {
        $stock = $this->articleService->getAvailableStock($article, $start, $end);

        return [
            'available' => (float) $stock['available'],
            'reserved' => (float) $stock['reserved'],
            'total' => (float) $stock['total'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(InventoryArticle $article): array
    {
        $mainImage = $article->images->first();

        return [
            'id' => $article->id,
            'name' => $article->name,
            'inventory_number' => $article->inventory_number,
            'category' => $article->category === null
                ? null
                : ['id' => $article->category->id, 'name' => $article->category->name],
            'subcategory' => $article->subCategory === null
                ? null
                : ['id' => $article->subCategory->id, 'name' => $article->subCategory->name],
            'thumbnail_url' => $mainImage === null
                ? null
                : $this->publicUrl($mainImage->thumbnail ?: $mainImage->image),
            'room' => $this->room($article->room),
            'is_detailed_quantity' => $article->is_detailed_quantity,
            'ready_quantity' => $article->readyQuantity(),
            'total_quantity' => (float) $article->quantity,
        ];
    }

    /**
     * Stock per status; units count into the status they are in.
     *
     * @return list<array{id: int, name: string, color: string|null, quantity: float, ready: bool}>
     */
    private function statuses(InventoryArticle $article): array
    {
        $readyId = InventoryArticleStatus::defaultStatusId();
        $row = static fn (InventoryArticleStatus $status, float $quantity): array => [
            'id' => $status->id,
            'name' => $status->name,
            'color' => $status->color,
            'quantity' => $quantity,
            'ready' => $status->id === $readyId,
        ];

        if (!$article->is_detailed_quantity) {
            return $article->statusValues
                ->map(fn (InventoryArticleStatus $status): array => $row($status, (float) $this->pivotValue($status)))
                ->values()
                ->all();
        }

        return $article->detailedArticleQuantities
            ->filter(static fn (InventoryDetailedQuantityArticle $unit): bool => $unit->status !== null)
            ->groupBy('inventory_article_status_id')
            ->sortBy(static fn ($units): int => (int) $units->first()->status->order)
            ->map(static fn ($units): array => $row(
                $units->first()->status,
                (float) $units->sum('quantity'),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function unit(InventoryDetailedQuantityArticle $unit): array
    {
        return [
            'id' => $unit->id,
            'inventory_number' => $unit->inventory_number,
            'name' => $unit->name ?: null,
            'quantity' => (float) $unit->quantity,
            'status' => $unit->status === null
                ? null
                : ['id' => $unit->status->id, 'name' => $unit->status->name, 'color' => $unit->status->color],
            'room' => $this->room($unit->room),
            'properties' => $this->properties($unit),
        ];
    }

    /**
     * Filled properties as display values: the manufacturer by name, a checkbox as "1" or "0",
     * everything else as stored. The room is a field of its own.
     *
     * @return list<array{id: int, name: string, type: string, value: string}>
     */
    private function properties(InventoryArticle|InventoryDetailedQuantityArticle $owner): array
    {
        $rows = [];
        foreach ($owner->properties as $property) {
            $stored = $this->pivotValue($property);
            $hidden = in_array($property->type, self::HIDDEN_PROPERTY_TYPES, true);
            if ($hidden || $stored === null || $stored === '') {
                continue;
            }

            $value = match ($property->type) {
                'manufacturer' => $owner->manufacturer['name'] ?? null,
                'checkbox' => filter_var($stored, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                default => (string) $stored,
            };
            if ($value === null) {
                continue;
            }

            $rows[] = ['id' => $property->id, 'name' => $property->name, 'type' => $property->type, 'value' => $value];
        }

        return $rows;
    }

    /**
     * @param array{id: int, name: string, property_id: int}|null $room
     * @return array{id: int, name: string}|null
     */
    private function room(?array $room): ?array
    {
        return $room === null ? null : ['id' => $room['id'], 'name' => $room['name']];
    }

    /** The value a status (quantity) or property carries on the pivot it was loaded through. */
    private function pivotValue(Model $model): mixed
    {
        return $model->getRelation('pivot')->getAttribute('value');
    }

    /** Article images live on the public disk, like in the web. */
    private function publicUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
