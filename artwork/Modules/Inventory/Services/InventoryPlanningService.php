<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Repositories\ExternalIssueRepository;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon as CarbonCarbon;
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
        }, 'project', 'specialItems', 'files', 'responsibleUsers'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }, 'issuedBy', 'receivedBy', 'files', 'specialItems'])
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
     * Calculate availability timeline segments using a sweep-line algorithm.
     * Produces segments showing how availability changes over time within the range.
     *
     * @param int $totalAvailable "Einsatzbereit" quantity
     * @param SupportCollection $internalIssues filtered internal issues (with pivot->quantity)
     * @param SupportCollection $externalIssues filtered external issues (with pivot->quantity)
     * @param string $rangeStart Y-m-d
     * @param string $rangeEnd Y-m-d
     * @return array{segments: array, min_available: int, peak_usage: int}
     */
    public function calculateAvailabilityTimeline(
        int $totalAvailable,
        SupportCollection $internalIssues,
        SupportCollection $externalIssues,
        string $rangeStart,
        string $rangeEnd
    ): array {
        $events = [];

        foreach ($internalIssues as $issue) {
            $qty = (int) ($issue->pivot->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $start = CarbonCarbon::parse($issue->start_date)->startOfDay();
            $end = CarbonCarbon::parse($issue->end_date ?? $issue->start_date)->endOfDay();

            $events[] = ['ts' => $start->timestamp, 'delta' => $qty, 'date' => $start->toDateString()];
            $events[] = ['ts' => $end->timestamp + 1, 'delta' => -$qty, 'date' => $end->copy()->addDay()->toDateString()];
        }

        foreach ($externalIssues as $issue) {
            $qty = (int) ($issue->pivot->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $start = CarbonCarbon::parse($issue->issue_date)->startOfDay();
            $end = CarbonCarbon::parse($issue->return_date ?? $issue->issue_date)->endOfDay();

            $events[] = ['ts' => $start->timestamp, 'delta' => $qty, 'date' => $start->toDateString()];
            $events[] = ['ts' => $end->timestamp + 1, 'delta' => -$qty, 'date' => $end->copy()->addDay()->toDateString()];
        }

        // If no issues, return a single segment covering the entire range
        if (empty($events)) {
            return [
                'segments' => [[
                    'start' => $rangeStart,
                    'end' => $rangeEnd,
                    'usage' => 0,
                    'available' => $totalAvailable,
                    'days' => CarbonCarbon::parse($rangeStart)->diffInDays(CarbonCarbon::parse($rangeEnd)) + 1,
                ]],
                'min_available' => $totalAvailable,
                'peak_usage' => 0,
            ];
        }

        // Sort by timestamp; on tie, process removals before additions
        usort($events, function ($a, $b) {
            if ($a['ts'] !== $b['ts']) {
                return $a['ts'] <=> $b['ts'];
            }
            return $a['delta'] <=> $b['delta'];
        });

        // Build segments from event-driven changes
        $segments = [];
        $currentUsage = 0;
        $peakUsage = 0;
        $minAvailable = $totalAvailable;

        // Collect unique boundary dates
        $boundaries = [$rangeStart];
        foreach ($events as $event) {
            $boundaries[] = $event['date'];
        }
        // Add one day after range end as final boundary
        $boundaries[] = CarbonCarbon::parse($rangeEnd)->addDay()->toDateString();
        $boundaries = array_unique($boundaries);
        sort($boundaries);

        // Build a map: date -> total delta at that date
        $deltaMap = [];
        foreach ($events as $event) {
            $deltaMap[$event['date']] = ($deltaMap[$event['date']] ?? 0) + $event['delta'];
        }

        // Sweep through boundaries to create segments
        $currentUsage = 0;
        for ($i = 0; $i < count($boundaries) - 1; $i++) {
            $segStart = $boundaries[$i];
            // Apply any deltas at this boundary
            if (isset($deltaMap[$segStart])) {
                $currentUsage += $deltaMap[$segStart];
            }

            $segEnd = CarbonCarbon::parse($boundaries[$i + 1])->subDay()->toDateString();

            // Skip segments outside our range
            if ($segEnd < $rangeStart || $segStart > $rangeEnd) {
                continue;
            }

            // Clamp to range
            $segStart = max($segStart, $rangeStart);
            $segEnd = min($segEnd, $rangeEnd);

            if ($segStart > $segEnd) {
                continue;
            }

            $available = $totalAvailable - $currentUsage;
            $days = CarbonCarbon::parse($segStart)->diffInDays(CarbonCarbon::parse($segEnd)) + 1;

            // Merge with previous segment if same usage
            if (!empty($segments) && $segments[count($segments) - 1]['usage'] === $currentUsage) {
                $lastIdx = count($segments) - 1;
                $segments[$lastIdx]['end'] = $segEnd;
                $segments[$lastIdx]['days'] = CarbonCarbon::parse($segments[$lastIdx]['start'])
                        ->diffInDays(CarbonCarbon::parse($segEnd)) + 1;
            } else {
                $segments[] = [
                    'start' => $segStart,
                    'end' => $segEnd,
                    'usage' => $currentUsage,
                    'available' => $available,
                    'days' => $days,
                ];
            }

            $peakUsage = max($peakUsage, $currentUsage);
            $minAvailable = min($minAvailable, $available);
        }

        // Ensure we have at least one segment
        if (empty($segments)) {
            $segments[] = [
                'start' => $rangeStart,
                'end' => $rangeEnd,
                'usage' => 0,
                'available' => $totalAvailable,
                'days' => CarbonCarbon::parse($rangeStart)->diffInDays(CarbonCarbon::parse($rangeEnd)) + 1,
            ];
            $minAvailable = $totalAvailable;
        }

        return [
            'segments' => $segments,
            'min_available' => $minAvailable,
            'peak_usage' => $peakUsage,
        ];
    }

    /**
     * Get the "Einsatzbereit" quantity for an article.
     */
    private function getEinsatzbereitQuantity(InventoryArticle $article): int
    {
        if ($article->is_detailed_quantity) {
            return (int) $article->detailedArticleQuantities
                ->filter(fn ($dq) => $dq->status && $dq->status->name === 'Einsatzbereit')
                ->sum('quantity');
        }

        $readyStatus = $article->statusValues->firstWhere('name', 'Einsatzbereit');
        return $readyStatus ? (int) $readyStatus->pivot->value : 0;
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
        $article = InventoryArticle::with([
            'category', 'subCategory', 'statusValues',
            'detailedArticleQuantities', 'detailedArticleQuantities.status',
        ])->findOrFail($articleId);

        $internal = InternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }, 'project'])
            ->whereDate('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('end_date', '>=', $startDate)
                    ->orWhereNull('end_date');
            })
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->whereDate('issue_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('return_date', '>=', $startDate)
                    ->orWhereNull('return_date');
            })
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

        // Filter to only issues that actually have articles for this article
        $filteredInternal = $internal->filter(fn($i) => $i->articles->isNotEmpty())->values();
        $filteredExternal = $external->filter(fn($e) => $e->articles->isNotEmpty())->values();

        // Collect article pivot data for sweep-line (from filtered issues)
        // Format dates as Y-m-d strings to avoid Carbon double-time parsing issues
        $internalForSweep = collect();
        foreach ($filteredInternal as $issue) {
            foreach ($issue->articles as $a) {
                $internalForSweep->push((object) [
                    'start_date' => $issue->start_date ? CarbonCarbon::parse($issue->start_date)->format('Y-m-d') : null,
                    'start_time' => $issue->start_time,
                    'end_date' => $issue->end_date ? CarbonCarbon::parse($issue->end_date)->format('Y-m-d') : null,
                    'end_time' => $issue->end_time,
                    'pivot' => (object) ['quantity' => $a->pivot->quantity],
                ]);
            }
        }

        $externalForSweep = collect();
        foreach ($filteredExternal as $issue) {
            foreach ($issue->articles as $a) {
                $externalForSweep->push((object) [
                    'issue_date' => $issue->issue_date ? CarbonCarbon::parse($issue->issue_date)->format('Y-m-d') : null,
                    'return_date' => $issue->return_date ? CarbonCarbon::parse($issue->return_date)->format('Y-m-d') : null,
                    'pivot' => (object) ['quantity' => $a->pivot->quantity],
                ]);
            }
        }

        // Calculate peak concurrent usage (sweep-line)
        $peakUsage = InventoryArticle::calculatePeakConcurrentUsage($internalForSweep, $externalForSweep);

        // Calculate "Einsatzbereit" total
        $einsatzbereit = $this->getEinsatzbereitQuantity($article);

        // Calculate availability timeline
        $timeline = $this->calculateAvailabilityTimeline(
            $einsatzbereit,
            $internalForSweep,
            $externalForSweep,
            $startDate,
            $endDate
        );

        // Naive sum for backward compatibility
        $issuedQuantity = 0;
        foreach ($filteredInternal as $issue) {
            foreach ($issue->articles as $a) {
                $issuedQuantity += (int) ($a->pivot->quantity ?? 0);
            }
        }
        foreach ($filteredExternal as $issue) {
            foreach ($issue->articles as $a) {
                $issuedQuantity += (int) ($a->pivot->quantity ?? 0);
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
            'internal' => $filteredInternal,
            'external' => $filteredExternal,
            'issued_quantity' => $issuedQuantity,
            'peak_usage' => $peakUsage,
            'min_available' => $timeline['min_available'],
            'availability_timeline' => $timeline['segments'],
        ];
    }
}
