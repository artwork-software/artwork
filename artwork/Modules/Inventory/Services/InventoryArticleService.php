<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Repositories\InventoryCategoryRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class InventoryArticleService
{
    /**
     * @param InventoryArticleRepository $articleRepository
     */
    public function __construct(
        protected readonly InventoryArticleRepository $articleRepository,
        protected readonly AuthManager $auth,
    ) {
    }

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
        ?string $search = '',
        ?array $resolvedFilters = null,
        ?array $resolvedTagIds = null
    ): LengthAwarePaginator {
        $query = $this->buildArticleQuery($category, $subCategory, $search);

        $query->with([
            'category',
            'subCategory',
            'properties',
            'images' => function ($query): void {
                $query->orderBy('is_main_image', 'desc')->orderBy('id');
            },
            'statusValues',
            'detailedArticleQuantities.status',
            'tags',
            'tags.allowedUsers',
            'tags.allowedDepartments',
        ]);

        // Property-Filter
        $filters = $resolvedFilters ?? [];
        $query = $this->articleRepository->applyFilters($query, $filters);

        // Tag-Filter
        $tagIds = $resolvedTagIds ?? [];
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds): void {
                $q->whereIn('inventory_tags.id', $tagIds);
            });
        }

        $perPage = Request::get('per_page', Request::integer('entitiesPerPage', 50));
        $this->applyStableOrdering($query, $category, $subCategory);
        return $query->paginate($perPage);
    }

    protected function applyStableOrdering(Builder $query, ?InventoryCategory $category, ?InventorySubCategory $subCategory): void
    {
        // Wenn eine Subkategorie fix ist: innerhalb einfach nach Name
        if ($subCategory) {
            $query->orderBy('inventory_articles.name', 'asc')
                ->orderBy('inventory_articles.id', 'asc');
            return;
        }

        // Wenn Kategorie fix ist: nach Subkategorie-Name, dann Artikelname
        if ($category) {
            $query->leftJoin('inventory_sub_categories as isc', 'isc.id', '=', 'inventory_articles.inventory_sub_category_id')
                ->select('inventory_articles.*')
                ->orderBy('isc.name', 'asc')
                ->orderBy('inventory_articles.name', 'asc')
                ->orderBy('inventory_articles.id', 'asc');
            return;
        }

        // Global: Kategorie -> Subkategorie -> Artikelname
        $query->leftJoin('inventory_categories as ic', 'ic.id', '=', 'inventory_articles.inventory_category_id')
            ->leftJoin('inventory_sub_categories as isc', 'isc.id', '=', 'inventory_articles.inventory_sub_category_id')
            ->select('inventory_articles.*')
            ->orderBy('ic.name', 'asc')
            ->orderBy('isc.name', 'asc')
            ->orderBy('inventory_articles.name', 'asc')
            ->orderBy('inventory_articles.id', 'asc');
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
    ): Builder {
        $query = $this->articleRepository->baseQuery()->withoutTrashed();

        // Kategorie/Subkategorie IMMER anwenden (auch bei Search)
        if ($category) {
            $query->where('inventory_articles.inventory_category_id', $category->id);
        }

        if ($subCategory) {
            $query->where('inventory_articles.inventory_sub_category_id', $subCategory->id);
        }

        // Search anwenden (innerhalb der gewählten Kategorie/Subkategorie)
        if (!empty($search)) {
            $ids = $this->articleRepository->search($search)->pluck('id');
            $query->whereIn('inventory_articles.id', $ids);
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
     * Summiert Status-Zähler:
     * - Haupt-Artikel:  pivot->value
     * - Detail-Artikel: detailedArticleQuantities.quantity (gruppiert nach deren Status)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCountsByStatus($articles): array
    {

        // 1) Aggregation Haupt-Artikel (Pivot->value)
        $main = $articles
            ->flatMap(static fn ($article) => $article->statusValues->map(static fn ($status) => [
                'id'    => (int) $status->id,
                'name'  => $status->name,
                'color' => $status->color ?? '#ccc',
                'count' => (int) ($status->pivot->value ?? 0),
            ]))
            ->groupBy('id')
            ->map(static fn ($rows) => [
                'name'  => $rows->first()['name'],
                'color' => $rows->first()['color'] ?? '#ccc',
                'count' => $rows->sum('count'),
            ]);

        // 2) Aggregation Detail-Artikel (quantity pro Detail-Status)
        $detail = $articles
            ->flatMap(static fn ($article) => $article->detailedArticleQuantities
                ->filter(static fn ($d) => $d->status !== null)
                ->map(static fn ($d) => [
                    'id'    => (int) $d->status->id,
                    'name'  => $d->status->name,
                    'color' => $d->status->color ?? '#ccc',
                    'count' => (int) ($d->quantity ?? 0),
                ]))
            ->groupBy('id')
            ->map(static fn ($rows) => [
                'name'  => $rows->first()['name'],
                'color' => $rows->first()['color'] ?? '#ccc',
                'count' => $rows->sum('count'),
            ]);

        // 3) Mergen: Haupt-Artikel + Detail-Artikel addieren
        $merged = $main->toArray();
        foreach ($detail as $id => $row) {
            if (!isset($merged[$id])) {
                $merged[$id] = ['name' => $row['name'], 'count' => 0];
            }
            $merged[$id]['count'] += $row['count'];
        }

        ksort($merged, SORT_NUMERIC);
        return $merged;
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

            // 🔹 NEU: Tags verarbeiten & Berechtigungen prüfen
            $this->processArticleTags($article, $request->input('tag_ids', []));

            return $article->load(['properties', 'images', 'statusValues', 'tags']);
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
            // Vorherige Werte sichern mit Null-Handling
            $oldQuantity = $article->quantity ?? null;

            // Sicheres Zugreifen auf statusValues mit mehrfacher Null-Prüfung
            $oldStatus1 = null;
            if ($article->statusValues && ($status1 = $article->statusValues->firstWhere('id', 1))) {
                $oldStatus1 = isset($status1->pivot) && isset($status1->pivot->value) ? $status1->pivot->value : null;
            }

            // Detailed Articles: Status 1 Werte sichern mit Null-Handling
            $oldDetailedStatus1 = [];
            // Prüfe, ob detailedArticleQuantities existiert, bevor darauf zugegriffen wird
            if ($article && isset($article->detailedArticleQuantities)) {
                foreach ($article->detailedArticleQuantities as $detailed) {
                    // Stelle sicher, dass detailed, status und id existieren
                    if (
                        $detailed && isset($detailed->status) && isset($detailed->status->id) &&
                        $detailed->status->id == 1 && isset($detailed->id)
                    ) {
                        // Sicheres Zugreifen auf pivot und value
                        if (isset($detailed->status->pivot) && isset($detailed->status->pivot->value)) {
                            $oldDetailedStatus1[$detailed->id] = $detailed->status->pivot->value;
                        } elseif (isset($detailed->status->value)) {
                            $oldDetailedStatus1[$detailed->id] = $detailed->status->value;
                        } else {
                            $oldDetailedStatus1[$detailed->id] = null;
                        }
                    }
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

            // 🔹 NEU: Tags verarbeiten + Berechtigungen prüfen
            $this->processArticleTags($article, $request->input('tag_ids', []));

            // Artikel neu laden inkl. Status, Detailed-Status und Tags
            $article = $article->fresh(['detailedArticleQuantities.status', 'statusValues', 'tags']);

            // Nachherige Werte prüfen mit verbessertem Null-Handling
            $newQuantity = $article ? ($article->quantity ?? null) : null;

            // Sicheres Zugreifen auf statusValues mit mehrfacher Null-Prüfung
            $newStatus1 = null;
            if ($article && $article->statusValues && ($status1 = $article->statusValues->firstWhere('id', 1))) {
                $newStatus1 = isset($status1->pivot) && isset($status1->pivot->value) ? $status1->pivot->value : null;
            }

            $detailedStatus1Changed = false;
            // Ensure detailedArticleQuantities exists before iterating
            if ($article && $article->detailedArticleQuantities) {
                foreach ($article->detailedArticleQuantities as $detailed) {
                    // Ensure detailed and status objects exist and have required properties
                    if ($detailed && $detailed->status && $detailed->status->id == 1 && isset($detailed->id)) {
                        $old = $oldDetailedStatus1[$detailed->id] ?? null;
                        // Ensure pivot exists before accessing its properties
                        $new = null;
                        if (isset($detailed->status->pivot) && isset($detailed->status->pivot->value)) {
                            $new = $detailed->status->pivot->value;
                        } elseif (isset($detailed->status->value)) {
                            $new = $detailed->status->value;
                        }

                        if (is_numeric($old) && is_numeric($new) && $new < $old) {
                            $detailedStatus1Changed = true;
                            break;
                        }
                    }
                }
            }

            // Null-safe comparison of quantities and status values
            $quantityChanged = $oldQuantity !== null && $newQuantity !== null && $oldQuantity != $newQuantity;
            $status1Changed = $oldStatus1 !== null && $newStatus1 !== null && $oldStatus1 != $newStatus1;

            if ($quantityChanged || $status1Changed || $detailedStatus1Changed) {
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
                    $notificationTitle = __('notification.inventory_article_overbooked_title', ['issueName' => $issue->name, 'articleName' => $article->name], $user->language);
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.inventory_article_overbooked_description', ['issueName' => $issue->name, 'articleName' => $article->name], $user->language)
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
                $notificationTitle = __('notification.inventory_article_overbooked_title', ['issueName' => $issue->name, 'articleName' => $article->name], $user->language);
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.inventory_article_overbooked_description', ['issueName' => $issue->name, 'articleName' => $article->name], $user->language)
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
    protected function processArticleImages(InventoryArticle $article, StoreInventoryArticleRequest|UpdateInventoryArticleRequest $request): void
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
    protected function processArticleProperties(InventoryArticle $article, StoreInventoryArticleRequest|UpdateInventoryArticleRequest $request): void
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

    /**
     * Verknüpft Tags mit dem Artikel und prüft, ob der aktuelle User diese Tags verwenden darf.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function processArticleTags(InventoryArticle $article, array $tagIds): void
    {
        // Kein Tag übergeben → alles detach(en)
        if (empty($tagIds)) {
            if (method_exists($article, 'tags')) {
                $article->tags()->sync([]);
            }
            return;
        }

        /** @var \Artwork\Modules\User\Models\User|\App\Models\User|null $user */
        $user = $this->auth->user();

        if (! $user) {
            throw ValidationException::withMessages([
                'tag_ids' => [__('You must be logged in to assign tags.')],
            ]);
        }

        // Tags + Berechtigungs-Beziehungen laden
        $tags = InventoryTag::query()
            ->with([
                'allowedUsers:id',
                'allowedDepartments:id',
            ])
            ->whereIn('id', $tagIds)
            ->get();

        // Departments des aktuellen Users (Relation-Namen bitte ggf. anpassen)
        $userDepartmentIds = method_exists($user, 'departments')
            ? $user->departments()->pluck('departments.id')->all()
            : [];

        $unauthorized = [];

        foreach ($tags as $tag) {
            // Unrestricted Tags → immer erlaubt
            if (! $tag->has_restricted_permissions) {
                continue;
            }

            $allowedUserIds = $tag->allowedUsers->pluck('id')->all();
            $allowedDepartmentIds = $tag->allowedDepartments->pluck('id')->all();

            $isUserExplicitlyAllowed = in_array($user->id, $allowedUserIds, true);
            $hasMatchingDepartment = ! empty(array_intersect($userDepartmentIds, $allowedDepartmentIds));

            if (! $isUserExplicitlyAllowed && ! $hasMatchingDepartment) {
                $unauthorized[] = $tag;
            }
        }

        if (! empty($unauthorized)) {
            throw ValidationException::withMessages([
                'tag_ids' => [__('You are not allowed to use one or more selected tags.')],
            ]);
        }

        // Alles OK → Tags syncen
        if (method_exists($article, 'tags')) {
            $article->tags()->sync($tags->pluck('id')->all());
        }
    }
}
