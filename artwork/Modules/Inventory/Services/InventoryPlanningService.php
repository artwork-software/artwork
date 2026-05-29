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

        $articleIds = $articles->pluck('id')->all();
        $rangeStart = $dates->first()['date'];
        $rangeEnd   = $dates->last()['date'];

        // Issues nur EINMAL laden — werden für Availability-Berechnung
        // UND für die Bar-Darstellung im Frontend genutzt.
        $internalIssues = $this->loadInternalIssuesInRange($articleIds, $rangeStart, $rangeEnd);
        $externalIssues = $this->loadExternalIssuesInRange($articleIds, $rangeStart, $rangeEnd);

        $grouped = $this->groupArticles($articles);
        $availability = $this->calculateAvailabilityWithFlag(
            $articles,
            $dates,
            $internalIssues,
            $externalIssues
        );
        $issuesData = $this->collectIssuesForRange($internalIssues, $externalIssues);

        return [
            'groupedArticles' => array_values($grouped),
            'availability'    => $availability,
            'dates'           => $dates,
            'dataArray'       => [$start->format('Y-m-d'), $end->format('Y-m-d')],
            'issues'          => $issuesData['issues'],
            'projects'        => $issuesData['projects'],
        ];
    }

    /**
     * Load internal issues overlapping with [rangeStart, rangeEnd], eager-loading
     * only the articles the user can actually see.
     *
     * @param array<int, int> $articleIds
     * @return Collection<int, InternalIssue>
     */
    protected function loadInternalIssuesInRange(array $articleIds, string $rangeStart, string $rangeEnd): Collection
    {
        return InternalIssue::with([
            'articles' => function ($query) use ($articleIds) {
                $query->whereIn('inventory_articles.id', $articleIds);
            },
            'project:id,name',
        ])
            // B11: columns are date-typed; using plain comparisons keeps the
            // index usable (whereDate wraps in DATE() which kills index scans).
            ->where('start_date', '<=', $rangeEnd)
            ->where('end_date', '>=', $rangeStart)
            ->get();
    }

    /**
     * Load external issues overlapping with [rangeStart, rangeEnd], eager-loading
     * only the articles the user can actually see.
     *
     * @param array<int, int> $articleIds
     * @return Collection<int, ExternalIssue>
     */
    protected function loadExternalIssuesInRange(array $articleIds, string $rangeStart, string $rangeEnd): Collection
    {
        return ExternalIssue::with([
            'articles' => function ($query) use ($articleIds) {
                $query->whereIn('inventory_articles.id', $articleIds);
            },
            'receivedBy:id,first_name,last_name',
        ])
            // B11: date-typed columns — plain comparison enables index usage.
            ->where('issue_date', '<=', $rangeEnd)
            ->where('return_date', '>=', $rangeStart)
            ->get();
    }

    /**
     * Project the already-loaded internal and external issues into a flat
     * structure for the planning bars.
     *
     * @param Collection<int, InternalIssue> $internalIssues
     * @param Collection<int, ExternalIssue> $externalIssues
     * @return array{issues: array<int, array<string, mixed>>, projects: array<int, array{id:int,name:string}>}
     */
    protected function collectIssuesForRange(Collection $internalIssues, Collection $externalIssues): array
    {
        $issues = [];
        $projects = [];

        foreach ($internalIssues as $issue) {
            if ($issue->articles->isEmpty()) {
                continue;
            }

            $articleQuantities = [];
            foreach ($issue->articles as $article) {
                $articleQuantities[(int) $article->id] = (int) ($article->pivot->quantity ?? 0);
            }

            $projectName = $issue->project?->name;
            $projectId   = $issue->project?->id;
            if ($projectId !== null && $projectName !== null && !isset($projects[$projectId])) {
                $projects[$projectId] = ['id' => $projectId, 'name' => $projectName];
            }

            $issues[] = [
                'id'                 => $issue->id,
                'type'               => 'intern',
                'name'               => $issue->name,
                'start'              => Carbon::parse($issue->start_date)->toDateString(),
                'end'                => Carbon::parse($issue->end_date ?? $issue->start_date)->toDateString(),
                'project_id'         => $projectId,
                'project_name'       => $projectName,
                'receiver_name'      => null,
                'article_ids'        => array_keys($articleQuantities),
                'article_quantities' => $articleQuantities,
            ];
        }

        foreach ($externalIssues as $issue) {
            if ($issue->articles->isEmpty()) {
                continue;
            }

            $articleQuantities = [];
            foreach ($issue->articles as $article) {
                $articleQuantities[(int) $article->id] = (int) ($article->pivot->quantity ?? 0);
            }

            $receiverName = $issue->external_name;
            if (empty($receiverName) && $issue->receivedBy !== null) {
                $receiverName = trim(
                    ($issue->receivedBy->first_name ?? '') . ' ' . ($issue->receivedBy->last_name ?? '')
                );
            }

            $issues[] = [
                'id'                 => $issue->id,
                'type'               => 'extern',
                'name'               => $issue->name ?? $receiverName ?? ('Leihschein #' . $issue->id),
                'start'              => Carbon::parse($issue->issue_date)->toDateString(),
                'end'                => Carbon::parse($issue->return_date ?? $issue->issue_date)->toDateString(),
                'project_id'         => null,
                'project_name'       => null,
                'receiver_name'      => $receiverName !== '' ? $receiverName : null,
                'article_ids'        => array_keys($articleQuantities),
                'article_quantities' => $articleQuantities,
            ];
        }

        usort($issues, static function (array $a, array $b): int {
            return strcmp($a['start'], $b['start']) ?: ($a['id'] <=> $b['id']);
        });

        $projectsList = array_values($projects);
        usort($projectsList, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return [
            'issues'   => $issues,
            'projects' => $projectsList,
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
     * Group articles by category and subcategory.
     *
     * B5: Uses a dictionary index for sub-category lookup (O(1) per insert)
     * instead of `array_search(array_column(...))` which made grouping O(N²).
     *
     * @param Collection<int, InventoryArticle> $articles
     * @return array<string, mixed>
     */
    protected function groupArticles(Collection $articles): array
    {
        $grouped = [];
        $subIndex = []; // [category][subName] => int (position in $grouped[category]['subcategories'])

        foreach ($articles as $article) {
            $category = $article->category->name ?? 'Sonstige';
            $subCategory = $article->subCategory->name ?? null;

            if (!isset($grouped[$category])) {
                $grouped[$category] = ['category' => $category, 'articles' => [], 'subcategories' => []];
                $subIndex[$category] = [];
            }

            if ($subCategory) {
                if (!isset($subIndex[$category][$subCategory])) {
                    $grouped[$category]['subcategories'][] = ['name' => $subCategory, 'articles' => [$article]];
                    $subIndex[$category][$subCategory] = count($grouped[$category]['subcategories']) - 1;
                } else {
                    $idx = $subIndex[$category][$subCategory];
                    $grouped[$category]['subcategories'][$idx]['articles'][] = $article;
                }
            } else {
                $grouped[$category]['articles'][] = $article;
            }
        }

        return $grouped;
    }

    /**
     * Calculate article availability for each date in a COMPACT representation.
     *
     * Output shape:
     *   [
     *     'base'   => [articleId => einsatzbereitQuantity, ...],
     *     'deltas' => [date => [articleId => actualValue, ...], ...]
     *   ]
     *
     * Only cells that DEVIATE from `base[articleId]` are stored in `deltas`.
     * The frontend falls back to `base[articleId]` for any missing entry —
     * which is the typical case (no issue overlap) and used to balloon the
     * JSON payload to N_articles × N_dates entries (B7).
     *
     * Performance notes:
     *  - B1: Issues are passed in (loaded once in the caller) instead of re-queried.
     *  - B2: Re-uses the articles already loaded by `getAvailabilityData()`.
     *  - B3: For each issue we compute the start/end date-index ONCE and then
     *        iterate only over the relevant slice of `$dateList`.
     *  - B4: "Einsatzbereit" quantity is computed once per article.
     *  - B7: Compact representation; `usedFlag` removed entirely (the FE can
     *        derive "used" from the per-article issues array we already ship).
     *
     * @param Collection<int, InventoryArticle> $articles
     * @param SupportCollection $dates
     * @param Collection<int, InternalIssue> $internalIssues
     * @param Collection<int, ExternalIssue> $externalIssues
     * @return array{base: array<int, int>, deltas: array<string, array<int, int>>}
     */
    protected function calculateAvailabilityWithFlag(
        Collection $articles,
        SupportCollection $dates,
        Collection $internalIssues,
        Collection $externalIssues
    ): array {
        // Pre-compute "Einsatzbereit" quantity per article (date-independent).
        $base = [];
        foreach ($articles as $article) {
            $base[$article->id] = $this->getEinsatzbereitQuantity($article);
        }

        // Build a flat list of date strings + lookup index for O(1) bounds resolution.
        $dateList = [];
        $dateIndex = [];
        foreach ($dates as $i => $dateInfo) {
            $dateList[$i] = $dateInfo['date'];
            $dateIndex[$dateInfo['date']] = $i;
        }
        $rangeStart = $dateList[0] ?? null;
        $rangeEnd   = $dateList[count($dateList) - 1] ?? null;

        // `deltas[$date][$articleId]` holds the ACTUAL value (already adjusted).
        // We create entries lazily — only when an issue actually subtracts.
        $deltas = [];

        if ($rangeStart === null || $rangeEnd === null) {
            return ['base' => $base, 'deltas' => $deltas];
        }

        $applyIssue = function (
            string $startDate,
            string $endDate,
            iterable $issueArticles
        ) use (&$deltas, $base, $dateList, $dateIndex, $rangeStart, $rangeEnd): void {
            $effectiveStart = $startDate < $rangeStart ? $rangeStart : $startDate;
            $effectiveEnd   = $endDate   > $rangeEnd   ? $rangeEnd   : $endDate;

            if ($effectiveStart > $rangeEnd || $effectiveEnd < $rangeStart) {
                return;
            }

            $startIdx = $dateIndex[$effectiveStart] ?? null;
            $endIdx   = $dateIndex[$effectiveEnd]   ?? null;
            if ($startIdx === null || $endIdx === null) {
                return;
            }

            $impact = [];
            foreach ($issueArticles as $article) {
                $impact[$article->id] = (int) ($article->pivot->quantity ?? 0);
            }
            if (empty($impact)) {
                return;
            }

            for ($i = $startIdx; $i <= $endIdx; $i++) {
                $date = $dateList[$i];
                foreach ($impact as $articleId => $qty) {
                    // Initialize delta with base on first touch.
                    if (!isset($deltas[$date][$articleId])) {
                        $deltas[$date][$articleId] = $base[$articleId] ?? 0;
                    }
                    $deltas[$date][$articleId] -= $qty;
                }
            }
        };

        foreach ($internalIssues as $issue) {
            $applyIssue(
                Carbon::parse($issue->start_date)->toDateString(),
                Carbon::parse($issue->end_date ?? $issue->start_date)->toDateString(),
                $issue->articles
            );
        }

        foreach ($externalIssues as $issue) {
            $applyIssue(
                Carbon::parse($issue->issue_date)->toDateString(),
                Carbon::parse($issue->return_date ?? $issue->issue_date)->toDateString(),
                $issue->articles
            );
        }

        return [
            'base'   => $base,
            'deltas' => $deltas,
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
            // B11: date-typed columns — plain comparison uses the index.
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }, 'issuedBy', 'receivedBy', 'files', 'specialItems'])
            ->where('issue_date', '<=', $date)
            ->where('return_date', '>=', $date)
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
            // B11: date-typed columns — plain comparison uses the index.
            ->where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->where('end_date', '>=', $startDate)
                    ->orWhereNull('end_date');
            })
            ->get();

        $external = ExternalIssue::with(['articles' => function ($query) use ($articleId) {
            $query->where('inventory_article_id', $articleId);
        }])
            ->where('issue_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->where('return_date', '>=', $startDate)
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
