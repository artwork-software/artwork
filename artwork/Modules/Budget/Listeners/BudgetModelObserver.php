<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetModelObserver
{
    public function saved(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    private function invalidateForModel(Model $model): void
    {
        $projectId = $this->resolveProjectId($model);

        if ($projectId) {
            BudgetUpdated::dispatch($projectId);
        }
    }

    private function resolveProjectId(Model $model): ?int
    {
        if ($model instanceof Table) {
            return $model->project_id;
        }

        if ($model instanceof Column) {
            return DB::table('tables')
                ->where('id', $model->table_id)
                ->value('project_id');
        }

        if ($model instanceof MainPosition) {
            return DB::table('tables')
                ->where('id', $model->table_id)
                ->value('project_id');
        }

        if ($model instanceof SubPosition) {
            return DB::table('main_positions')
                ->join('tables', 'tables.id', '=', 'main_positions.table_id')
                ->where('main_positions.id', $model->main_position_id)
                ->value('tables.project_id');
        }

        if ($model instanceof SubPositionRow) {
            return DB::table('sub_positions')
                ->join('main_positions', 'main_positions.id', '=', 'sub_positions.main_position_id')
                ->join('tables', 'tables.id', '=', 'main_positions.table_id')
                ->where('sub_positions.id', $model->sub_position_id)
                ->value('tables.project_id');
        }

        if ($model instanceof ColumnCell) {
            return DB::table('sub_position_rows')
                ->join('sub_positions', 'sub_positions.id', '=', 'sub_position_rows.sub_position_id')
                ->join('main_positions', 'main_positions.id', '=', 'sub_positions.main_position_id')
                ->join('tables', 'tables.id', '=', 'main_positions.table_id')
                ->where('sub_position_rows.id', $model->sub_position_row_id)
                ->value('tables.project_id');
        }

        if ($model instanceof SageAssignedData) {
            return DB::table('column_sub_position_row')
                ->join('sub_position_rows', 'sub_position_rows.id', '=', 'column_sub_position_row.sub_position_row_id')
                ->join('sub_positions', 'sub_positions.id', '=', 'sub_position_rows.sub_position_id')
                ->join('main_positions', 'main_positions.id', '=', 'sub_positions.main_position_id')
                ->join('tables', 'tables.id', '=', 'main_positions.table_id')
                ->where('column_sub_position_row.id', $model->column_cell_id)
                ->value('tables.project_id');
        }

        if ($model instanceof SageNotAssignedData) {
            return $model->project_id;
        }

        return null;
    }
}
