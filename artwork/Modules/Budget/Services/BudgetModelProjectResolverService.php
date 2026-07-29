<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Database\Eloquent\Model;

class BudgetModelProjectResolverService
{
    /**
     * Memoisiert die Projekt-Aufloesung pro Modell (Klasse + ID). Ohne Memo
     * kostet jede Zell-Speicherung bis zu 4-5 Lazy-Queries fuer die
     * Relationskette bis zur project_id - bei Bursts (Spalte anlegen,
     * Sage-Import) multipliziert sich das. Die Zuordnung Zelle->Projekt
     * aendert sich nie, daher ist das Memo gefahrlos.
     *
     * @var array<string, int|null>
     */
    private static array $memo = [];

    public function resolveProjectId(Model $model): ?int
    {
        $key = $model::class . ':' . $model->getKey();

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        if (count(self::$memo) > 2000) {
            self::$memo = [];
        }

        return self::$memo[$key] = $this->doResolveProjectId($model);
    }

    private function doResolveProjectId(Model $model): ?int
    {
        return match (true) {
            $model instanceof Table => $model->project_id,
            $model instanceof Column => $this->resolveFromColumn($model),
            $model instanceof MainPosition => $this->resolveFromMainPosition($model),
            $model instanceof SubPosition => $this->resolveFromSubPosition($model),
            $model instanceof SubPositionRow => $this->resolveFromSubPositionRow($model),
            $model instanceof ColumnCell => $this->resolveFromColumnCell($model),
            $model instanceof SageAssignedData => $this->resolveFromSageAssignedData($model),
            $model instanceof SageNotAssignedData => $model->project_id,
            default => null,
        };
    }

    private function resolveFromColumn(Column $column): ?int
    {
        return $column->table?->project_id;
    }

    private function resolveFromMainPosition(MainPosition $mainPosition): ?int
    {
        return $mainPosition->table?->project_id;
    }

    private function resolveFromSubPosition(SubPosition $subPosition): ?int
    {
        return $subPosition->mainPosition?->table?->project_id;
    }

    private function resolveFromSubPositionRow(SubPositionRow $subPositionRow): ?int
    {
        return $subPositionRow->subPosition?->mainPosition?->table?->project_id;
    }

    private function resolveFromColumnCell(ColumnCell $columnCell): ?int
    {
        return $columnCell->subPositionRow?->subPosition?->mainPosition?->table?->project_id;
    }

    private function resolveFromSageAssignedData(SageAssignedData $sageAssignedData): ?int
    {
        return $sageAssignedData->columnCell?->subPositionRow?->subPosition?->mainPosition?->table?->project_id;
    }
}
