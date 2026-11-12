<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiInventoryArticleData;
use Artwork\Modules\AiAssistant\DTOs\AiInventoryListData;
use Artwork\Modules\AiAssistant\DTOs\AiInventoryReservationData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Inventarverfuegbarkeit fuer artwork-ai (core-api-inventory; Konzept B1 §3).
 *
 * Gerechnet wird nicht selbst: `InventoryArticle::getAvailableStock()` ist dieselbe Methode, die
 * Artikelseite und Projekt-Tab benutzen (Spitzenlast per Sweep-Line, Menge im Standard-Status
 * laut `InventoryArticle::readyQuantity()`). Sie wertet **geladene** Relationen ungefiltert aus;
 * die Ausgaben werden deshalb schon beim Eager Loading auf den Zeitraum eingeschraenkt.
 */
class AiInventoryReadService
{
    public function availability(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?string $search,
        ?int $limit
    ): AiInventoryListData {
        $limit = $this->limit($limit);
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $query = InventoryArticle::query()
            ->visibleFor($user)
            ->when(
                $search !== null && $search !== '',
                fn (Builder $builder) => $builder->where(
                    'name',
                    'like',
                    '%' . $this->escapeLike((string) $search) . '%'
                )
            );

        $total = (clone $query)->count();

        /** @var Collection<int, InventoryArticle> $articles */
        $articles = $query
            ->with($this->eagerLoads($fromDate, $toDate))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $projectNames = $this->visibleProjectNames($user, $articles);

        return new AiInventoryListData(
            data: $articles->map(
                fn (InventoryArticle $article): AiInventoryArticleData => $this->articleData(
                    $article,
                    $fromDate,
                    $toDate,
                    $projectNames
                )
            )->all(),
            period: AiPeriodData::fromDates($from, $to),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function eagerLoads(string $fromDate, string $toDate): array
    {
        return [
            'category',
            'statusValues',
            'detailedArticleQuantities.status',
            'internalIssues' => fn ($relation) => $relation
                ->where('internal_issues.start_date', '<=', $toDate)
                ->where(
                    fn ($builder) => $builder
                        ->where('internal_issues.end_date', '>=', $fromDate)
                        ->orWhereNull('internal_issues.end_date')
                ),
            'externalIssues' => fn ($relation) => $relation
                ->where('external_issues.issue_date', '<=', $toDate)
                ->where(
                    fn ($builder) => $builder
                        ->where('external_issues.return_date', '>=', $fromDate)
                        ->orWhereNull('external_issues.return_date')
                ),
        ];
    }

    /**
     * @param array<int, string> $projectNames
     */
    private function articleData(
        InventoryArticle $article,
        string $fromDate,
        string $toDate,
        array $projectNames
    ): AiInventoryArticleData {
        $stock = $this->stockFor($article, $fromDate, $toDate);

        return new AiInventoryArticleData(
            id: $article->id,
            name: AiText::truncate($article->name),
            category: AiRefData::make($article->inventory_category_id, $article->category?->name),
            total: $stock['total'],
            reserved: $stock['reserved'],
            available: $stock['available'],
            next_reservation: $this->nextReservation($article, $projectNames),
        );
    }

    /**
     * Bestand im Zeitraum. `total` ist die Menge im Standard-Status; ohne gepflegte Statusmengen
     * faellt sie auf die Gesamtmenge des Artikels zurueck. Beides entscheidet
     * `InventoryArticle::readyQuantity()`, dieselbe Quelle wie Artikelseite, Planung und Projekt-Tab.
     *
     * @return array{total: float, reserved: float, available: float}
     */
    private function stockFor(InventoryArticle $article, string $fromDate, string $toDate): array
    {
        $stock = $article->getAvailableStock($fromDate, $toDate);

        return [
            'total' => (float) $stock['total'],
            'reserved' => (float) $stock['reserved'],
            'available' => (float) $stock['available'],
        ];
    }

    /**
     * Die frueheste Ausgabe im Zeitraum. Der Projektname steht nur dort, wo das Projekt fuer die
     * fragende Person sichtbar ist; die belegte Menge bleibt in jedem Fall stehen.
     *
     * @param array<int, string> $projectNames
     */
    private function nextReservation(InventoryArticle $article, array $projectNames): ?AiInventoryReservationData
    {
        $candidates = [
            ...$this->reservations($article->internalIssues, 'start_date', 'end_date'),
            ...$this->reservations($article->externalIssues, 'issue_date', 'return_date'),
        ];

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (array $a, array $b): int => $a['start']->getTimestamp() <=> $b['start']->getTimestamp()
        );

        $next = $candidates[0];
        $projectId = $next['project_id'];
        $projectName = $projectId === null ? null : ($projectNames[$projectId] ?? null);

        return new AiInventoryReservationData(
            project: $projectName === null ? null : AiRefData::make($projectId, $projectName),
            period: AiPeriodData::fromDates($next['start'], $next['end']),
            quantity: $next['quantity'],
        );
    }

    /**
     * @param iterable<int, object> $issues
     * @return list<array{start: CarbonInterface, end: CarbonInterface, project_id: int|null, quantity: int}>
     */
    private function reservations(iterable $issues, string $startField, string $endField): array
    {
        $reservations = [];

        foreach ($issues as $issue) {
            $start = $issue->{$startField};

            if (!$start instanceof CarbonInterface) {
                continue;
            }

            $end = $issue->{$endField};

            $reservations[] = [
                'start' => $start,
                'end' => $end instanceof CarbonInterface ? $end : $start,
                'project_id' => $issue->project_id === null ? null : (int) $issue->project_id,
                'quantity' => (int) ($issue->pivot->quantity ?? 0),
            ];
        }

        return $reservations;
    }

    /**
     * Namen der Projekte, die diese Person sehen darf — eine Abfrage fuer alle Ausgaben.
     *
     * @param Collection<int, InventoryArticle> $articles
     * @return array<int, string>
     */
    private function visibleProjectNames(User $user, Collection $articles): array
    {
        $projectIds = [];

        foreach ($articles as $article) {
            foreach ([$article->internalIssues, $article->externalIssues] as $issues) {
                foreach ($issues as $issue) {
                    if ($issue->project_id !== null) {
                        $projectIds[] = (int) $issue->project_id;
                    }
                }
            }
        }

        if ($projectIds === []) {
            return [];
        }

        /** @var array<int, string> $names */
        $names = Project::query()
            ->visibleFor($user)
            ->whereIn('id', array_values(array_unique($projectIds)))
            ->pluck('name', 'id')
            ->map(static fn (mixed $name): string => (string) $name)
            ->all();

        return $names;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}
