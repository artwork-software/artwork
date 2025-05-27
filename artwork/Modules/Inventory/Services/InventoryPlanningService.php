<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;

class InventoryPlanningService
{
    public function __construct(
        protected InventoryArticleRepository $articleRepo
    ) {}

    public function getAvailabilityData(User $user): array
    {
        $filter = $user->inventoryArticlePlanFilter;
        $start = $filter->start_date ? Carbon::parse($filter->start_date) : Carbon::now()->startOfMonth();
        $end = $filter->end_date ? Carbon::parse($filter->end_date) : Carbon::now()->endOfMonth();

        $dates = collect(range(0, $start->diffInDays($end)))->map(fn($i) => [
            'date' => $start->copy()->addDays($i)->toDateString(),
            'isWeekend' => $start->copy()->addDays($i)->isWeekend(),
        ]);

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

    protected function groupArticles($articles): array
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

    protected function calculateAvailability($articles, $dates): array
    {
        $availability = [];

        foreach ($dates as $dateInfo) {
            $date = $dateInfo['date'];
            foreach ($articles as $article) {
                $availability[$date][$article->id] = $article->quantity ?? 0;
            }
        }

        $internal = InternalIssue::with('articles')->get();
        foreach ($internal as $issue) {
            foreach ($dates as $dateInfo) {
                $date = $dateInfo['date'];
                if ($date >= $issue->start_date && $date <= $issue->end_date) {
                    foreach ($issue->articles as $article) {
                        $availability[$date][$article->id] -= $article->pivot->quantity;
                    }
                }
            }
        }

        $external = ExternalIssue::with('articles')->get();
        foreach ($external as $issue) {
            foreach ($dates as $dateInfo) {
                $date = $dateInfo['date'];
                if ($date >= $issue->issue_date && $date <= $issue->return_date) {
                    foreach ($issue->articles as $article) {
                        $availability[$date][$article->id] -= $article->pivot->quantity;
                    }
                }
            }
        }

        return $availability;
    }
}
