<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiBudgetActualData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetColumnData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetCommentedData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetMainPositionData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetMoneySourceData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetProjectData;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetSummaryData;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetSumCalculator;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;

/**
 * Budgetzusammenfassung eines Projekts (ai-scenarios-3 S2).
 *
 * Summen ausschliesslich ueber `BudgetSumCalculator::computeAndAttachSums()` auf dem eager
 * geladenen Baum (nicht `Table::calculateSums`), Spalte ueber
 * `ColumnRelevanceService::resolveRelevantColumn()`. Keine Abhaengigkeit von `request()`/`Auth`.
 *
 * Recht wie `ProjectTabSageInvoiceOverviewService::hasBudgetAccess()`: globales Budgetrecht, Admin
 * oder `access_budget` am Projekt — sonst 403 `no_budget_access`.
 */
class AiBudgetSummaryService
{
    private const MAIN_POSITION_LIMIT = 20;

    private const COST = 'BUDGET_TYPE_COST';

    public function __construct(
        private readonly BudgetSumCalculator $sumCalculator,
        private readonly ColumnRelevanceService $columnRelevance,
        private readonly MoneySourceCalculationService $moneySources,
        private readonly BiDerivedValuesService $biValues,
    ) {
    }

    public static function hasBudgetAccess(User $user, Project $project): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->checkPermissionTo(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value, 'web')
            || $project->access_budget()->where('users.id', $user->id)->exists();
    }

    public function summary(User $user, Project $project): AiBudgetSummaryData
    {
        if (!self::hasBudgetAccess($user, $project)) {
            AiErrors::abort(AiErrors::NO_BUDGET_ACCESS);
        }

        $projectData = new AiBudgetProjectData(
            (int) $project->id,
            AiText::truncate($project->name),
            (bool) $project->is_group
        );
        $sage = $this->biValues->isSageApiEnabled();
        $table = $this->loadTable($project, $sage);
        $column = $table === null ? null : $this->columnRelevance->resolveRelevantColumn($table->columns);

        if ($table === null || $column === null) {
            return new AiBudgetSummaryData(
                project: $projectData,
                has_budget: false,
                currency: null,
                column: null,
                costs: null,
                earnings: null,
                result: null,
                profitable: null,
                commented: null,
                main_positions: [],
                money_sources: [],
                actual: null,
            );
        }

        $this->sumCalculator->computeAndAttachSums($table);
        $columnId = (int) $column->id;

        $costs = (float) ($table->getAttribute('costSums')[$columnId] ?? 0);
        $earnings = (float) ($table->getAttribute('earningSums')[$columnId] ?? 0);
        $result = $earnings - $costs;

        return new AiBudgetSummaryData(
            project: $projectData,
            has_budget: true,
            currency: null,
            column: new AiBudgetColumnData(
                $columnId,
                AiText::truncate($column->name),
                AiText::truncate($column->subName)
            ),
            costs: self::money($costs),
            earnings: self::money($earnings),
            result: self::money($result),
            profitable: round($result, 2) > 0,
            commented: new AiBudgetCommentedData(
                self::money((float) ($table->getAttribute('commentedCostSums')[$columnId] ?? 0)),
                self::money((float) ($table->getAttribute('commentedEarningSums')[$columnId] ?? 0)),
            ),
            main_positions: $this->mainPositions($table, $columnId),
            money_sources: $this->moneySourcesOf($project),
            actual: $sage ? $this->actual($table, $column) : null,
        );
    }

    public static function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function loadTable(Project $project, bool $sage): ?Table
    {
        $cells = ['mainPositions.subPositions.subPositionRows.cells.column'];

        if ($sage) {
            $cells[] = 'mainPositions.subPositions.subPositionRows.cells.sageAssignedData';
        }

        /** @var Table|null $table */
        $table = Table::query()
            ->where('project_id', $project->id)
            ->with(array_merge([
                'columns' => static fn ($query) => $query->orderBy('position'),
                'mainPositions' => static fn ($query) => $query->orderBy('position'),
                'mainPositions.subPositions' => static fn ($query) => $query->orderBy('position'),
                'mainPositions.subPositions.subPositionRows',
            ], $cells))
            ->first();

        return $table;
    }

    /**
     * @return list<AiBudgetMainPositionData>
     */
    private function mainPositions(Table $table, int $columnId): array
    {
        return $table->mainPositions
            ->take(self::MAIN_POSITION_LIMIT)
            ->map(static fn (MainPosition $position): AiBudgetMainPositionData => new AiBudgetMainPositionData(
                name: AiText::truncate($position->name),
                type: $position->type === self::COST ? 'cost' : 'earning',
                sum: self::money((float) ($position->getAttribute('columnSums')[$columnId]['sum'] ?? 0)),
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<AiBudgetMoneySourceData>
     */
    private function moneySourcesOf(Project $project): array
    {
        return $project->moneySources()
            ->get()
            ->map(function (MoneySource $source): AiBudgetMoneySourceData {
                $amount = (float) str_replace(',', '.', (string) ($source->amount ?? 0));
                $linked = $this->moneySources->getPositionSumOfOneMoneySource($source);

                return new AiBudgetMoneySourceData(
                    id: (int) $source->id,
                    name: AiText::truncate($source->name),
                    amount: self::money($amount),
                    linked_sum: self::money($linked),
                    remaining: self::money($amount + $linked),
                );
            })
            ->values()
            ->all();
    }

    /**
     * Ist-Werte: in Sage gebuchte Betraege (`sage_value`, von `computeAndAttachSums` gesetzt) der
     * relevanten Spalte, ohne auskommentierte Zeilen; fehlende Buchungen zaehlen als 0.00.
     */
    private function actual(Table $table, Column $column): AiBudgetActualData
    {
        $costs = 0.0;
        $earnings = 0.0;

        foreach ($table->mainPositions as $mainPosition) {
            foreach ($mainPosition->subPositions as $subPosition) {
                foreach ($subPosition->subPositionRows as $row) {
                    if ($row->commented) {
                        continue;
                    }

                    foreach ($row->cells as $cell) {
                        if ((int) $cell->column_id !== (int) $column->id || $cell->commented) {
                            continue;
                        }

                        $value = (float) ($cell->getAttribute('sage_value') ?? 0);
                        $mainPosition->type === self::COST ? $costs += $value : $earnings += $value;
                    }
                }
            }
        }

        return new AiBudgetActualData(self::money($costs), self::money($earnings), self::money($earnings - $costs));
    }
}
