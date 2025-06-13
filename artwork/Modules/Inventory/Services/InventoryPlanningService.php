<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
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
        protected readonly InventoryArticleRepository $articleRepo
    ) {}

    /**
     * Get availability data for planning
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function getAvailabilityData(User $user): array
    {
        $filter = $user->inventoryArticlePlanFilter;
        if (!$filter) {
            $filter = (object)[
                'start_date' => null,
                'end_date' => null,
            ];
        }
        $start = $filter->start_date ? Carbon::parse($filter->start_date) : Carbon::now()->startOfMonth();
        $end = $filter->end_date ? Carbon::parse($filter->end_date) : Carbon::now()->endOfMonth();

        $dates = $this->generateDateRange($start, $end);

        /** @var Collection<int, InventoryArticle> $articles */
        $articles = $this->articleRepo->getAllWithCategories();
        $grouped = $this->groupArticles($articles);

        $availability = $this->calculateAvailability($articles, $dates);

        return [
            'groupedArticles' => array_values($grouped),
            'availability' => $availability,
            'dates' => $dates,
            'dataArray' => [$start->format('Y-m-d'), $end->format('Y-m-d')],
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
     * Calculate article availability for each date
     *
     * @param Collection<int, InventoryArticle> $articles
     * @param SupportCollection $dates
     * @return array<string, array<int, int>>
     */
    protected function calculateAvailability(Collection $articles, SupportCollection $dates): array
    {
        $availability = [];

        // Initialize availability with base quantities
        foreach ($dates as $dateInfo) {
            $date = $dateInfo['date'];
            foreach ($articles as $article) {
                $availability[$date][$article->id] = $article->quantity ?? 0;
            }
        }

        // Process internal issues with eager loading to reduce queries
        InternalIssue::with(['articles' => function($query) use ($articles) {
            $query->whereIn('inventory_articles.id', $articles->pluck('id'));
        }])
            ->whereBetween('start_date', [$dates->first()['date'], $dates->last()['date']])
            ->orWhereBetween('end_date', [$dates->first()['date'], $dates->last()['date']])
            ->get()
            ->each(function ($issue) use (&$availability, $dates) {
                foreach ($dates as $dateInfo) {
                    $date = $dateInfo['date'];
                    if ($date >= $issue->start_date && $date <= $issue->end_date) {
                        foreach ($issue->articles as $article) {
                            $availability[$date][$article->id] -= $article->pivot->quantity;
                        }
                    }
                }
            });

        // Process external issues with eager loading to reduce queries
        ExternalIssue::with(['articles' => function($query) use ($articles) {
            $query->whereIn('inventory_articles.id', $articles->pluck('id'));
        }])
            ->whereBetween('issue_date', [$dates->first()['date'], $dates->last()['date']])
            ->orWhereBetween('return_date', [$dates->first()['date'], $dates->last()['date']])
            ->get()
            ->each(function ($issue) use (&$availability, $dates) {
                foreach ($dates as $dateInfo) {
                    $date = $dateInfo['date'];
                    if ($date >= $issue->issue_date && $date <= $issue->return_date) {
                        foreach ($issue->articles as $article) {
                            $availability[$date][$article->id] -= $article->pivot->quantity;
                        }
                    }
                }
            });

        return $availability;
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
        $article = InventoryArticle::with(['category', 'subCategory', 'statusValues'])
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
                    'value' => $statusValue->pivot->value,
                ])->toArray(),
            ],
            'date' => $date,
            'internal' => $internal->filter(fn($i) => $i->articles->isNotEmpty())->values(),
            'external' => $external->filter(fn($e) => $e->articles->isNotEmpty())->values(),
        ];
    }
}
