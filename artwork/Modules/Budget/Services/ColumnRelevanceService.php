<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Support\Collection;

/**
 * Verwaltet die budgetrelevante Spalte (Feld relevant_for_project_groups):
 * Pro Budgettabelle trägt genau eine Wertspalte das Flag. Diese Spalte liefert
 * die "Vorschau"-Werte der Budget-Exporte nach Stichtag und fließt bei
 * Projektgruppen in die Aggregation der Unterprojekte ein.
 */
class ColumnRelevanceService
{
    // Die ersten drei Spalten (KTO/KST/Position) sind strukturell und nie budgetrelevant.
    private const FIRST_VALUE_COLUMN_POSITION = 3;

    public function isFlaggable(Column $column): bool
    {
        return $column->type === 'empty' && $column->position >= self::FIRST_VALUE_COLUMN_POSITION;
    }

    /**
     * Liefert die budgetrelevante Spalte aus einer bereits geladenen
     * Column-Collection: die (hinterste) geflaggte Wertspalte, für Altbestand
     * ohne Flag als Fallback die hinterste Wertspalte in Position-Reihenfolge.
     * Nimmt die Collection statt der Tabelle entgegen, damit Exporte/BI ihre
     * eager-geladenen Relationen weiterverwenden können (kein N+1).
     */
    public function resolveRelevantColumn(Collection $columns): ?Column
    {
        $flaggable = $columns
            ->filter(fn(Column $column) => $this->isFlaggable($column))
            ->sortBy('position')
            ->values();

        return $flaggable->last(fn(Column $column) => $column->relevant_for_project_groups)
            ?? $flaggable->last();
    }

    /**
     * Setzt das Flag exklusiv auf die übergebene Spalte; alle anderen Spalten
     * der Tabelle verlieren es.
     */
    public function assignExclusive(Column $column): void
    {
        /** @var Table $table */
        $table = $column->table()->withTrashed()->first();

        $table->columns()
            ->whereKeyNot($column->id)
            ->where('relevant_for_project_groups', true)
            ->update(['relevant_for_project_groups' => false]);

        if (!$column->relevant_for_project_groups) {
            $column->update(['relevant_for_project_groups' => true]);
        }

        $this->invalidate($table);
    }

    /**
     * Repariert die Invariante "genau eine budgetrelevante Wertspalte":
     * Bei mehreren Flags bleibt die hinterste geflaggte Wertspalte übrig,
     * ohne Flag erhält die hinterste Wertspalte das Flag. Auf Nicht-Wertspalten
     * (Summe/Differenz/Sage/Unterprojekte) gesetzte Flags werden entfernt.
     */
    public function ensureSingleRelevantColumn(Table $table): void
    {
        $columns = $table->columns()->orderBy('position')->get();
        $flaggable = $columns->filter(fn(Column $column) => $this->isFlaggable($column));

        $target = $flaggable->filter(fn(Column $column) => $column->relevant_for_project_groups)->last()
            ?? $flaggable->last();

        $changed = false;
        foreach ($columns as $column) {
            $shouldFlag = $target !== null && $column->id === $target->id;
            if ($column->relevant_for_project_groups !== $shouldFlag) {
                $column->update(['relevant_for_project_groups' => $shouldFlag]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->invalidate($table);
        }
    }

    /**
     * True, wenn die Spalte die letzte verbleibende Wertspalte der Tabelle ist —
     * sie darf dann nicht gelöscht, sondern nur geleert werden.
     */
    public function isLastValueColumn(Column $column): bool
    {
        return $this->isFlaggable($column)
            && !$column->table->columns()
                ->whereKeyNot($column->id)
                ->where('type', 'empty')
                ->where('position', '>=', self::FIRST_VALUE_COLUMN_POSITION)
                ->exists();
    }

    private function invalidate(Table $table): void
    {
        // Bulk-Updates feuern keine Model-Events, daher Cache-Invalidierung explizit.
        if ($table->project_id) {
            BudgetUpdated::dispatch($table->project_id);
        }
    }
}
