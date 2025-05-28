<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;

class InventoryArticleService
{
    /**
     * @param InventoryArticleRepository $articleRepository
     */
    public function __construct(
        protected readonly InventoryArticleRepository $articleRepository
    ) {}

    /**
     * Get paginated list of articles with optional filters
     *
     * @param InventoryCategory|null $category
     * @param InventorySubCategory|null $subCategory
     * @param string|null $search
     * @return LengthAwarePaginator
     * @throws \JsonException
     */
    public function getArticleList(
        ?InventoryCategory $category = null,
        ?InventorySubCategory $subCategory = null,
        ?string $search = ''
    ): LengthAwarePaginator {
        $query = $this->buildArticleQuery($category, $subCategory, $search);
        
        // Optimiere durch Eager Loading aller benötigten Relationen
        $query->with([
            'category',
            'subCategory',
            'properties.property',
            'images' => function ($query) {
                $query->orderBy('is_main_image', 'desc')->orderBy('id');
            },
            'statusValues',
            'detailedArticleQuantities.status',
        ]);
        
        $filters = json_decode(Request::get('filters', '[]'), true, 512, JSON_THROW_ON_ERROR);
        $query = $this->articleRepository->applyFilters($query, $filters);

        return $query->paginate(Request::integer('entitiesPerPage', 50));
    }

    /**
     * Build the base query for article list with filters
     *
     * @param InventoryCategory|null $category
     * @param InventorySubCategory|null $subCategory
     * @param string|null $search
     * @return Builder|HasMany
     */
    protected function buildArticleQuery(
        ?InventoryCategory $category = null,
        ?InventorySubCategory $subCategory = null,
        ?string $search = ''
    ): Builder|HasMany
    {
        $query = $this->articleRepository->baseQuery();

        if ($search) {
            $ids = $this->articleRepository->search($search)->pluck('id');
            $query->whereIn('id', $ids);
        }

        if ($category && !$subCategory && !$search) {
            return $category->articles();
        }

        if ($category && $subCategory && !$search) {
            return $subCategory->articles();
        }

        return $query;
    }

    /**
     * Count all inventory articles
     *
     * @return int
     */
    public function count(): int
    {
        return $this->articleRepository->count();
    }

    /**
     * Store a new inventory article with all related data
     *
     * @param StoreInventoryArticleRequest $request
     * @return InventoryArticle
     */
    public function store(StoreInventoryArticleRequest $request): InventoryArticle
    {
        // Nutze Datenbank-Transaktionen für Konsistenz
        return DB::transaction(function () use ($request) {
            $article = $this->articleRepository->create([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'inventory_category_id' => $request->integer('inventory_category_id'),
                'inventory_sub_category_id' => $request->filled('inventory_sub_category_id')
                    ? $request->integer('inventory_sub_category_id')
                    : null,
                'quantity' => $request->integer('quantity'),
                'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
            ]);

            $this->processArticleImages($article, $request);
            $this->processArticleProperties($article, $request);
            $this->processStatusValues($article, $request->get('statusValues', []));

            return $article->load(['properties.property', 'images', 'statusValues']);
        });
    }

    /**
     * Update an existing inventory article
     *
     * @param InventoryArticle $article
     * @param UpdateInventoryArticleRequest $request
     * @return InventoryArticle
     */
    public function update(InventoryArticle $article, UpdateInventoryArticleRequest $request): InventoryArticle
    {

        if (!$article->exists) {
            throw new \InvalidArgumentException('Article not found');
        }

        return DB::transaction(function () use ($article, $request) {
            // Vorherige Werte sichern
            $oldQuantity = $article->quantity;
            $oldStatus1 = optional($article->statusValues->firstWhere('id', 1))->pivot->value;

            // Detailed Articles: Status 1 Werte sichern
            $oldDetailedStatus1 = [];
            foreach ($article->detailedArticleQuantities as $detailed) {
                if ($detailed->status && $detailed->status->id == 1) {
                    $oldDetailedStatus1[$detailed->id] = $detailed->status->pivot->value ?? $detailed->status->value;
                }
            }

            $data = [
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'inventory_category_id' => $request->integer('inventory_category_id'),
                'quantity' => $request->integer('quantity'),
                'is_detailed_quantity' => $request->boolean('is_detailed_quantity'),
            ];

            if ($request->filled('inventory_sub_category_id')) {
                $data['inventory_sub_category_id'] = $request->integer('inventory_sub_category_id');
            }

            $this->articleRepository->update($article, $data);
            $this->processRemovedImages($article, $request);
            $this->processArticleImages($article, $request);

            // Reset and update article properties and detailed articles
            $this->resetArticleRelations($article);
            $this->processArticleProperties($article->fresh(), $request);
            $this->processStatusValues($article, $request->get('statusValues', []));

            $article = $article->fresh(['detailedArticleQuantities.status', 'statusValues']);

            // Nachherige Werte prüfen
            $newQuantity = $article?->quantity;
            $newStatus1 = optional($article?->statusValues->firstWhere('id', 1))->pivot->value;

            $detailedStatus1Changed = false;
            foreach ($article?->detailedArticleQuantities as $detailed) {
                if ($detailed->status && $detailed->status->id == 1) {
                    $old = $oldDetailedStatus1[$detailed->id] ?? null;
                    $new = $detailed->status->pivot->value ?? $detailed->status->value;
                    if (is_numeric($old) && is_numeric($new) && $new < $old) {
                        $detailedStatus1Changed = true;
                        break;
                    }
                }
            }

            if ($oldQuantity != $newQuantity || $oldStatus1 != $newStatus1 || $detailedStatus1Changed) {
                $this->notifyResponsibleUsersOnArticleChange($article);
            }

            return $article;
        });
    }

    /**
     * Benachrichtige verantwortliche User bei Mengen-/Statusänderung
     */
    protected function notifyResponsibleUsersOnArticleChange(InventoryArticle $article): void
    {
        $notificationService = app(\Artwork\Modules\Notification\Services\NotificationService::class);
        $internalIssues = $article->internalIssues()
            ->where('end_date', '>=', now()->toDateString())
            ->get();
        foreach ($internalIssues as $issue) {
            foreach ($issue->responsibleUsers as $user) {
                $notificationTitle = __('notification.inventory_article_changed_title', ['articleName' => $article->name], $user->language);
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.inventory_article_changed_description', ['articleName' => $article->name], $user->language)
                    ],
                    2 => [
                        'type' => 'link',
                        'title' => __('notification.material_issue', ['issueName' => $issue->name], $user->language),
                        'href' => route('issue-of-material.index', ['issue' => $issue->id])
                    ]
                ];
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'warning',
                    'message' => $notificationTitle
                ];
                $notificationService->setTitle($notificationTitle);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setIcon('warning');
                $notificationService->setPriority(3);
                $notificationService->setNotificationConstEnum(\Artwork\Modules\Notification\Enums\NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setNotificationTo($user);
                $notificationService->setModelId($issue->id);
                $notificationService->createNotification();
            }
        }
        $externalIssues = $article->externalIssues()
            ->where('return_date', '>=', now()->toDateString())
            ->get();
        foreach ($externalIssues as $issue) {
            if ($issue->issuedBy) {
                $user = $issue->issuedBy;
                $notificationTitle = __('notification.inventory_article_changed_title', ['articleName' => $article->name], $user->language);
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.inventory_article_changed_description', ['articleName' => $article->name], $user->language)
                    ],
                    2 => [
                        'type' => 'link',
                        'title' => __('notification.material_issue', ['issueName' => $issue->name], $user->language),
                        'href' => route('extern-issue-of-material.index', ['issue' => $issue->id])
                    ]
                ];
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'warning',
                    'message' => $notificationTitle
                ];
                $notificationService->setTitle($notificationTitle);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setIcon('warning');
                $notificationService->setPriority(3);
                $notificationService->setNotificationConstEnum(\Artwork\Modules\Notification\Enums\NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setNotificationTo($user);
                $notificationService->setModelId($issue->id);
                $notificationService->createNotification();
            }
        }
    }

    /**
     * Prüft alle zukünftigen Materialausgaben auf Überbuchung und benachrichtigt Verantwortliche
     */
    public function checkAndNotifyOverbooking(InventoryArticle $article): void
    {
        $notificationService = app(\Artwork\Modules\Notification\Services\NotificationService::class);
        $now = now()->toDateString();
        $internalIssues = $article->internalIssues()->where('end_date', '>=', $now)->get();

        foreach ($internalIssues as $issue) {
            $totalPlanned = $article->internalIssues()
                ->where('end_date', '>=', $now)
                ->sum('issuable_inventory_article.quantity');
            if ($totalPlanned > $article->quantity) {
                foreach ($issue->responsibleUsers as $user) {
                    $notificationTitle = __('notification.inventory_article_overbooked_title', ['issueName' => $issue->name], $user->language);
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.inventory_article_overbooked_description', ['issueName' => $issue->name], $user->language)
                        ],
                        2 => [
                            'type' => 'link',
                            'title' => __('notification.material_issue', ['issueName' => $issue->name], $user->language),
                            'href' => route('issue-of-material.index', ['issue' => $issue->id])
                        ]
                    ];
                    $broadcastMessage = [
                        'id' => random_int(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setDescription($notificationDescription);
                    $notificationService->setIcon('red');
                    $notificationService->setPriority(3);
                    $notificationService->setNotificationConstEnum(\Artwork\Modules\Notification\Enums\NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED);
                    $notificationService->setBroadcastMessage($broadcastMessage);
                    $notificationService->setNotificationTo($user);
                    $notificationService->setModelId($issue->id);
                    $notificationService->createNotification();
                }
            }
        }
        $externalIssues = $article->externalIssues()->where('return_date', '>=', $now)->get();

        foreach ($externalIssues as $issue) {
            $totalPlanned = $article->externalIssues()
                ->where('return_date', '>=', $now)
                ->sum('issuable_inventory_article.quantity');
            if ($totalPlanned > $article->quantity && $issue->issuedBy) {
                $user = $issue->issuedBy;
                $notificationTitle = __('notification.inventory_article_overbooked_title', ['issueName' => $issue->name], $user->language);
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.inventory_article_overbooked_description', ['issueName' => $issue->name], $user->language)
                    ],
                    2 => [
                        'type' => 'link',
                        'title' => __('notification.material_issue', ['issueName' => $issue->name], $user->language),
                        'href' => route('extern-issue-of-material.index', ['issue' => $issue->id])
                    ]
                ];
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationService->setTitle($notificationTitle);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setIcon('red');
                $notificationService->setPriority(3);
                $notificationService->setNotificationConstEnum(\Artwork\Modules\Notification\Enums\NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setNotificationTo($user);
                $notificationService->setModelId($issue->id);
                $notificationService->createNotification();
            }
        }
    }

    /**
     * Process and store article images
     *
     * @param InventoryArticle $article
     * @param StoreInventoryArticleRequest|UpdateInventoryArticleRequest $request
     * @return void
     */
    protected function processArticleImages(InventoryArticle $article, $request): void
    {
        $images = $request->file('newImages') ?? [];
        if (count($images) > 0) {
            $mainImageIndex = $request->integer('main_image_index');
            $this->articleRepository->addImages($article, $images, $mainImageIndex);
        }
    }

    /**
     * Process and store article properties and detailed articles
     *
     * @param InventoryArticle $article
     * @param StoreInventoryArticleRequest|UpdateInventoryArticleRequest $request
     * @return void
     */
    protected function processArticleProperties(InventoryArticle $article, $request): void
    {
        $this->articleRepository->attachProperties($article, $request->collect('properties'));
        $this->articleRepository->addDetailedArticles($article, $request->collect('detailed_article_quantities'));
    }

    /**
     * Process removed images
     *
     * @param InventoryArticle $article
     * @param UpdateInventoryArticleRequest $request
     * @return void
     */
    protected function processRemovedImages(InventoryArticle $article, UpdateInventoryArticleRequest $request): void
    {
        $removedImageIds = $request->get('removed_image_ids');
        if (is_array($removedImageIds) && !empty($removedImageIds)) {
            $article->images()->whereIn('id', $removedImageIds)->delete();
        }
    }

    /**
     * Reset all article relations before re-attaching
     *
     * @param InventoryArticle $article
     * @return void
     */
    protected function resetArticleRelations(InventoryArticle $article): void
    {
        $this->articleRepository->detachAllProperties($article);
        $this->articleRepository->detachAllDetailedArticleProperties($article);
        $this->articleRepository->deleteAllDetailedArticles($article);
        $this->articleRepository->detachAllStatusValues($article);
    }

    /**
     * Process and attach status values
     *
     * @param InventoryArticle $article
     * @param array $statusValues
     * @return void
     */
    protected function processStatusValues(InventoryArticle $article, array $statusValues): void
    {
        if (!empty($statusValues)) {
            $this->articleRepository->attachStatusValues($article, $statusValues);
        }
    }

    /**
     * Delete an inventory article (soft delete)
     *
     * @param InventoryArticle $article
     * @return void
     */
    public function delete(InventoryArticle $article): void
    {
        $this->articleRepository->delete($article);
        Cache::forget('inventory_article_count');
    }

    /**
     * Get all soft-deleted articles
     *
     * @return Collection
     */
    public function getAllTrashed(): Collection
    {
        return $this->articleRepository->getAllTrashed();
    }

    /**
     * Force delete an article
     *
     * @param InventoryArticle $article
     * @return void
     */
    public function forceDelete(InventoryArticle $article): void
    {
        $this->articleRepository->forceDelete($article);
    }

    /**
     * Restore a soft-deleted article
     *
     * @param InventoryArticle $article
     * @return void
     */
    public function restore(InventoryArticle $article): void
    {
        $this->articleRepository->restore($article);
    }

    /**
     * Attach status values to an article
     *
     * @param InventoryArticle $article
     * @param array $statusValues
     * @return void
     */
    public function attachStatusValues(InventoryArticle $article, array $statusValues): void
    {
        $this->articleRepository->attachStatusValues($article, $statusValues);
    }

    /**
     * Get available stock information for an article in a date range
     *
     * @param InventoryArticle $article
     * @param string $startDate
     * @param string $endDate
     * @return array<string, mixed>
     */
    public function getAvailableStock(InventoryArticle $article, string $startDate, string $endDate): array
    {
        return $this->articleRepository->getAvailableStock($article, $startDate, $endDate);
    }
}
