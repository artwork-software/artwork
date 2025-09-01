<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Repositories\ExternalIssueRepository;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

class InventoryPlanningService
{
    /**
     * @param InventoryArticleRepository $articleRepo
     */
    public function __construct(
        protected readonly InventoryArticleRepository $articleRepo,
        protected readonly InventoryUserFilterService $filterService,
    ) {}


    public function getAvailabilityData(User $user): array
    {
        $filter = $user->inventoryArticlePlanFilter ?? (object)['start_date' => null, 'end_date' => null];

        $start = $filter->start_date ? Carbon::parse($filter->start_date) : Carbon::now()->startOfMonth();
        $end   = $filter->end_date   ? Carbon::parse($filter->end_date)   : Carbon::now()->endOfMonth();

        $dates = $this->generateDateRange($start, $end);

        // 1x laden: alles was Availability braucht
        $articles = $this->filterService->getFilteredArticlesNew($user)
            ->with([
                'detailedArticleQuantities',            // für is_detailed_quantity
                'detailedArticleQuantities.status',     // Status der Detail-Batches
                'statusValues'                          // Fallback/klassische Statuswerte
            ])
            ->get();

        $grouped = $this->groupArticles($articles);
        $availability = $this->calculateAvailabilityWithFlag($articles, $dates);

        return [
            'groupedArticles' => array_values($grouped),
            'availability'    => $availability,
            'dates'           => $dates,
            'dataArray'       => [$start->format('Y-m-d'), $end->format('Y-m-d')],
        ];
    }

    /**
     * Generate a date range collection
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return SupportCollection
     */
    protected function generateDateRange(Carbon $start, Carbon $end): SupportCollection
    {
        return collect(range(0, $start->diffInDays($end)))
            ->map(fn(int $i): array => [
                'date' => $start->copy()->addDays($i)->toDateString(),
                'isWeekend' => $start->copy()->addDays($i)->isWeekend(),
            ]);
    }

    /**
     * Group articles by category and subcategory
     *
     * @param Collection<int, InventoryArticle> $articles
     * @return array<string, mixed>
     */
    protected function groupArticles(Collection $articles): array
    {
        $grouped = [];

        foreach ($articles as $article) {
            $category = $article->category->name ?? 'Sonstige';
            $subCategory = $article->subCategory->name ?? null;

            $grouped[$category] ??= ['category' => $category, 'articles' => [], 'subcategories' => []];

            if ($subCategory) {
                $foundIndex = array_search($subCategory, array_column($grouped[$category]['subcategories'], 'name'), true);
                if ($foundIndex === false) {
                    $grouped[$category]['subcategories'][] = ['name' => $subCategory, 'articles' => [$article]];
                } else {
                    $grouped[$category]['subcategories'][$foundIndex]['articles'][] = $article;
                }
            } else {
                $grouped[$category]['articles'][] = $article;
            }
        }

        return $grouped;
    }

    /**
     * Calculate article availability and usage flag for each date
     *
     * @param Collection<int, InventoryArticle> $articles
     * @param SupportCollection $dates
     * @return array{0: array<string, array<int, int>>, 1: array<string, array<int, bool>>}
     */
    protected function calculateAvailabilityWithFlag(Collection $articles, SupportCollection $dates): array
    {
        $availability = [];
        $usedFlag = [];

        // Eager load detailed article quantities with their statuses
        $articlesWithDetails = InventoryArticle::with(['detailedArticleQuantities', 'detailedArticleQuantities.status', 'statusValues'])
            ->whereIn('id', $articles->pluck('id'))
            ->get();

        // Initialize availability and usedFlag
        foreach ($dates as $dateInfo) {
            $date = $dateInfo['date'];
            foreach ($articlesWithDetails as $article) {
                if ($article->is_detailed_quantity) {
                    $availableQuantity = $article->detailedArticleQuantities
                        ->filter(function ($detailedArticle) {
                            return $detailedArticle->status && $detailedArticle->status->id === 1;
                        })
                        ->sum('quantity');
                } else {
                    $availableStatus = $article->statusValues->firstWhere('inventory_article_status_id', 1);
                    $availableQuantity = $availableStatus ? $availableStatus->pivot->value : $article->quantity;
                }
                $availability[$date][$article->id] = $availableQuantity ?? 0;
                $usedFlag[$date][$article->id] = false;
            }
        }

        // Process internal issues
        InternalIssue::with(['articles' => function($query) use ($articles) {
            $query->whereIn('inventory_articles.id', $articles->pluck('id'));
        }])
            ->where(function($query) use ($dates) {
                $query->whereDate('start_date', '<=', $dates->last()['date'])
                      ->whereDate('end_date', '>=', $dates->first()['date']);
            })
            ->get()
            ->each(function ($issue) use (&$availability, &$usedFlag, $dates) {
                foreach ($dates as $dateInfo) {
                    $date = $dateInfo['date'];
                    $dateCarbon = Carbon::parse($date);
                    $startDate = Carbon::parse($issue->start_date)->startOfDay();
                    $endDate = Carbon::parse($issue->end_date)->endOfDay();

                    if ($dateCarbon->between($startDate, $endDate)) {
                        foreach ($issue->articles as $article) {
                            $availability[$date][$article->id] -= $article->pivot->quantity;
                            $usedFlag[$date][$article->id] = true;
                        }
                    }
                }
            });

        // Process external issues
        ExternalIssue::with(['articles' => function($query) use ($articles) {
            $query->whereIn('inventory_articles.id', $articles->pluck('id'));
        }])
            ->where(function($query) use ($dates) {
                $query->whereDate('issue_date', '<=', $dates->last()['date'])
                      ->whereDate('return_date', '>=', $dates->first()['date']);
            })
            ->get()
            ->each(function ($issue) use (&$availability, &$usedFlag, $dates) {
                foreach ($dates as $dateInfo) {
                    $date = $dateInfo['date'];
                    $dateCarbon = Carbon::parse($date);
                    $issueDate = Carbon::parse($issue->issue_date)->startOfDay();
                    $returnDate = Carbon::parse($issue->return_date)->endOfDay();

                    if ($dateCarbon->between($issueDate, $returnDate)) {
                        foreach ($issue->articles as $article) {
                            $availability[$date][$article->id] -= $article->pivot->quantity;
                            $usedFlag[$date][$article->id] = true;
                        }
                    }
                }
            });

        return [
            'availability' => $availability,
            'usedFlag' => $usedFlag
        ];
    }

    /**
     * Get detailed information for the modal view
     *
     * @param int $articleId
     * @param string $date
     * @return array<string, mixed>
     */
    public function getDetailsForModal(int $articleId, string $date): array
    {
        $article = InventoryArticle::with(['category', 'subCategory', 'statusValues', 'detailedArticleQuantities'])
            ->findOrFail($articleId);

        $internal = InternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->whereDate('issue_date', '<=', $date)
            ->whereDate('return_date', '>=', $date)
            ->get();

        // Calculate status counts
        $statusCounts = [];

        // Initialize counts for all statuses to 0
        foreach ($article->statusValues as $statusValue) {
            $statusCounts[$statusValue->id] = 0;
        }

        if ($article->is_detailed_quantity) {
            // For articles with detailed quantities, count by status
            foreach ($article->detailedArticleQuantities as $detailedArticle) {
                if ($detailedArticle->inventory_article_status_id) {
                    $statusId = $detailedArticle->inventory_article_status_id;
                    $statusCounts[$statusId] = ($statusCounts[$statusId] ?? 0) + $detailedArticle->quantity;
                }
            }
        } else {
            // For articles without detailed quantities, use the status values directly
            foreach ($article->statusValues as $statusValue) {
                $statusCounts[$statusValue->id] = $statusValue->pivot->value ?? 0;
            }
        }

        return [
            'article' => [
                'id' => $article->id,
                'name' => $article->name,
                'category' => $article->category->name ?? null,
                'sub_category' => $article->subCategory->name ?? null,
                'quantity' => $article->quantity,
                'status' => $article->statusValues->map(fn ($statusValue) => [
                    'id' => $statusValue->id,
                    'name' => $statusValue->name,
                    'color' => $statusValue->color,
                    'value' => $statusCounts[$statusValue->id] ?? 0,
                ])->toArray(),
            ],
            'date' => $date,
            'internal' => $internal->filter(fn($i) => $i->articles->isNotEmpty())->values(),
            'external' => $external->filter(fn($e) => $e->articles->isNotEmpty())->values(),
        ];
    }

    /**
     * Get detailed information for the modal view for a date range
     *
     * @param int $articleId
     * @param string $startDate
     * @param string $endDate
     * @return array<string, mixed>
     */
    public function getDetailsForModalRange(int $articleId, string $startDate, string $endDate): array
    {
        $article = InventoryArticle::with(['category', 'subCategory', 'statusValues', 'detailedArticleQuantities'])
            ->findOrFail($articleId);

        $internal = InternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->whereDate('issue_date', '<=', $endDate)
            ->whereDate('return_date', '>=', $startDate)
            ->get();

        // Statuszählungen für den Zeitraum
        $statusCounts = [];
        foreach ($article->statusValues as $statusValue) {
            $statusCounts[$statusValue->id] = 0;
        }

        if ($article->is_detailed_quantity) {
            foreach ($article->detailedArticleQuantities as $detailedArticle) {
                if ($detailedArticle->inventory_article_status_id) {
                    $statusId = $detailedArticle->inventory_article_status_id;
                    $statusCounts[$statusId] = ($statusCounts[$statusId] ?? 0) + $detailedArticle->quantity;
                }
            }
        } else {
            foreach ($article->statusValues as $statusValue) {
                $statusCounts[$statusValue->id] = $statusValue->pivot->value ?? 0;
            }
        }

        // Summiere die ausgegebenen Mengen im Zeitraum
        $issuedQuantity = 0;
        foreach ($internal as $issue) {
            foreach ($issue->articles as $a) {
                if ($a->pivot->quantity) {
                    $issuedQuantity += $a->pivot->quantity;
                }
            }
        }
        foreach ($external as $issue) {
            foreach ($issue->articles as $a) {
                if ($a->pivot->quantity) {
                    $issuedQuantity += $a->pivot->quantity;
                }
            }
        }

        return [
            'article' => [
                'id' => $article->id,
                'name' => $article->name,
                'category' => $article->category->name ?? null,
                'sub_category' => $article->subCategory->name ?? null,
                'quantity' => $article->quantity,
                'status' => $article->statusValues->map(fn ($statusValue) => [
                    'id' => $statusValue->id,
                    'name' => $statusValue->name,
                    'color' => $statusValue->color,
                    'value' => $statusCounts[$statusValue->id] ?? 0,
                ])->toArray(),
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'internal' => $internal->filter(fn($i) => $i->articles->isNotEmpty())->values(),
            'external' => $external->filter(fn($e) => $e->articles->isNotEmpty())->values(),
            'issued_quantity' => $issuedQuantity,
        ];
    }
}
