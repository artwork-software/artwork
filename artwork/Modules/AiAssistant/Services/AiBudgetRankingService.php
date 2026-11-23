<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiBudgetColumnData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetProjectData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetRankingData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetRankingEntryData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetRankingMissingColumnData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetSumCalculator;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Budget-Rangliste ueber alle sichtbaren Projekte (core-ai-aggregates, Entgrenzen P1): „Welches Projekt
 * hat die hoechsten Einnahmen in 2026?" in einem Aufruf statt einem Budgetabruf je Projekt.
 *
 * Spalte je Projekt: ohne `period` die budgetrelevante (`ColumnRelevanceService`), mit `period` die
 * Wertspalte, deren Name oder Untertitel den Zeitraum als eigenes Wort enthaelt; bei mehreren die
 * relevante, sonst die hinterste. Summen wie die Budgetzusammenfassung (`BudgetSumCalculator`).
 * Alle Tabellen werden gemeinsam geladen und gerechnet — die Zahl der Abfragen haengt nicht von der
 * Zahl der Projekte ab.
 */
class AiBudgetRankingService
{
    public function __construct(
        private readonly BudgetSumCalculator $sumCalculator,
        private readonly ColumnRelevanceService $columnRelevance,
        private readonly BiDerivedValuesService $biValues,
        private readonly AiBudgetSummaryService $summary,
    ) {
    }

    /**
     * @param array{
     *     period: ?string, sort: string, direction: string, limit: int, state_id: ?int, include_groups: bool
     * } $options
     */
    public function ranking(User $user, array $options): AiBudgetRankingData
    {
        /** @var EloquentCollection<int, Project> $projects */
        $projects = Project::query()
            ->visibleFor($user)
            ->when(!$options['include_groups'], fn (Builder $query) => $query->where('is_group', false))
            ->when($options['state_id'] !== null, fn (Builder $query) => $query->where('state', $options['state_id']))
            ->orderBy('name')
            ->get(['id', 'name', 'is_group']);

        $accessible = $this->withBudgetAccess($user, $projects);
        $sage = $this->biValues->isSageApiEnabled();

        /** @var Collection<int, Table> $tables erste Tabelle je Projekt, wie die Budgetzusammenfassung */
        $tables = Table::query()
            ->whereIn('project_id', $accessible->modelKeys())
            ->with(AiBudgetSummaryService::tableRelations($sage))
            ->orderBy('id')
            ->get()
            ->unique('project_id')
            ->keyBy('project_id');

        $chosen = [];
        $withoutBudget = [];
        $withoutPeriodColumn = [];

        foreach ($accessible as $project) {
            $table = $tables->get($project->id);
            $flaggable = $table === null
                ? collect()
                : $table->columns->filter(fn (Column $column): bool => $this->columnRelevance->isFlaggable($column));

            if ($flaggable->isEmpty()) {
                $withoutBudget[] = new AiRefData((int) $project->id, AiText::truncate($project->name));
                continue;
            }

            $column = $this->columnFor($flaggable, $options['period']);

            if ($column === null) {
                $withoutPeriodColumn[] = new AiBudgetRankingMissingColumnData(
                    (int) $project->id,
                    AiText::truncate($project->name),
                    $flaggable->sortBy('position')
                        ->map(fn (Column $c): string => (string) AiText::truncate(trim($c->name . ' ' . $c->subName)))
                        ->values()
                        ->all(),
                );
                continue;
            }

            $chosen[] = [$project, $table, $column];
        }

        $this->sumCalculator->computeAndAttachSumsForTables(array_map(fn (array $entry): Table => $entry[1], $chosen));

        $entries = collect($chosen)->map(function (array $entry) use ($sage): array {
            /** @var array{0: Project, 1: Table, 2: Column} $entry */
            [$project, $table, $column] = $entry;
            $columnId = (int) $column->id;
            $costs = (float) ($table->getAttribute('costSums')[$columnId] ?? 0);
            $earnings = (float) ($table->getAttribute('earningSums')[$columnId] ?? 0);

            return [
                'values' => ['earnings' => $earnings, 'costs' => $costs, 'result' => $earnings - $costs],
                'data' => new AiBudgetRankingEntryData(
                    project: new AiBudgetProjectData(
                        (int) $project->id,
                        AiText::truncate($project->name),
                        (bool) $project->is_group
                    ),
                    column: new AiBudgetColumnData(
                        $columnId,
                        AiText::truncate($column->name),
                        AiText::truncate($column->subName)
                    ),
                    earnings: AiBudgetSummaryService::money($earnings),
                    costs: AiBudgetSummaryService::money($costs),
                    result: AiBudgetSummaryService::money($earnings - $costs),
                    actual: $sage ? $this->summary->actual($table, $column) : null,
                ),
            ];
        });

        $sort = $options['sort'];
        $sign = $options['direction'] === 'asc' ? 1 : -1;
        $sorted = $entries->sort(
            fn (array $a, array $b): int => ($sign * ($a['values'][$sort] <=> $b['values'][$sort]))
                ?: strcmp((string) $a['data']->project->name, (string) $b['data']->project->name)
        )->values();

        return new AiBudgetRankingData(
            period: $options['period'],
            sort: $sort,
            direction: $options['direction'],
            projects: $sorted->take($options['limit'])->pluck('data')->all(),
            without_budget: $withoutBudget,
            without_period_column: $withoutPeriodColumn,
            without_access: $projects->count() - $accessible->count(),
            total: $sorted->count(),
            truncated: $sorted->count() > $options['limit'],
        );
    }

    /**
     * Wertspalte fuer den Zeitraum. Ohne Zeitraum die budgetrelevante Spalte. Mit Zeitraum: der Zeitraum
     * als eigenes Wort in Name oder Untertitel („2026" passt auf „2026 €" und „Plan 2026", nicht auf
     * „20260"); mehrere Treffer → die relevante, sonst die hinterste.
     *
     * @param Collection<int, Column> $flaggable
     */
    private function columnFor(Collection $flaggable, ?string $period): ?Column
    {
        if ($period === null) {
            return $this->columnRelevance->resolveRelevantColumn($flaggable);
        }

        $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($period, '/') . '(?![\p{L}\p{N}])/iu';
        $matches = $flaggable
            ->filter(fn (Column $column): bool => preg_match($pattern, (string) $column->name) === 1
                || preg_match($pattern, (string) $column->subName) === 1)
            ->sortBy('position')
            ->values();

        return $matches->last(fn (Column $column): bool => (bool) $column->relevant_for_project_groups)
            ?? $matches->last();
    }

    /**
     * Projekte, deren Budget die Person sehen darf — gleiche Regel wie `hasBudgetAccess()`, aber mit einer
     * Abfrage fuer alle Projekte.
     *
     * @param EloquentCollection<int, Project> $projects
     * @return EloquentCollection<int, Project>
     */
    private function withBudgetAccess(User $user, EloquentCollection $projects): EloquentCollection
    {
        if (AiBudgetSummaryService::hasGlobalBudgetAccess($user)) {
            return $projects;
        }

        $ids = DB::table('project_user')
            ->where('user_id', $user->id)
            ->where('access_budget', true)
            ->whereIn('project_id', $projects->modelKeys())
            ->pluck('project_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $projects->filter(fn (Project $project): bool => in_array((int) $project->id, $ids, true))->values();
    }
}
