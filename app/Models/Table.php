<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $is_template
 * @property int $project_id
 * @property string $created_at
 * @property string $updated_at
 */
class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'is_template'
    ];

    protected $casts = [
        'is_template' => 'boolean'
    ];

    protected $appends = [
        'costSums',
        'earningSums',
        'commentedCostSums',
        'commentedEarningSums',
        'costSumDetails',
        'earningSumDetails'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class, 'table_id', 'id');
    }

    public function mainPositions(): HasMany
    {
        return $this->hasMany(MainPosition::class, 'table_id', 'id');
    }

    protected function calculateCommentedSums($mainPositionIds): Collection
    {
        $subPositionIds =  SubPosition::query()
            ->whereIntegerInRaw('main_position_id', $mainPositionIds)
            ->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        return ColumnCell::query()
            ->whereRelation('column', 'commented', true)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->orWhere('commented', true)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->get()
            ->groupBy('column_id')
            ->mapWithKeys(function ($cells, $column_id) {
                return [ $column_id => $cells->sum('value')];
            });
    }

    protected function calculateSums($mainPositionIds): Collection
    {
        $subPositionIds =  SubPosition::query()
            ->whereIntegerInRaw('main_position_id', $mainPositionIds)
            ->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        return ColumnCell::query()
            ->whereRelation('column', 'commented', false)
            ->where('commented', false)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->get()
            ->groupBy('column_id')
            ->skip(3)
            ->mapWithKeys(function ($cells, $column_id) {
                return [ $column_id => $cells->sum('value')];
            });
    }

    protected function sumDetails(string $type): Collection
    {
        return BudgetSumDetails::whereIntegerInRaw('column_id', $this->columns()
            ->pluck('id'))
            ->where('type', $type)
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->keyBy('column_id')
            ->mapWithKeys(fn ($sumDetails, $columnId) => [
                $columnId => [
                    'hasComments' => $sumDetails->comments_count > 0,
                    'hasMoneySource' => $sumDetails->sum_money_source_count > 0,
                ]
            ]);
    }

    public function getCostSumDetailsAttribute(): Collection
    {
        return $this->sumDetails("COST");
    }

    public function getEarningSumDetailsAttribute(): Collection
    {
        return $this->sumDetails("EARNING");
    }

    public function getCostSumsAttribute(): Collection
    {
        $mainPositionIds = $this->mainPositions()->where('type', 'BUDGET_TYPE_COST')->pluck('id');

        return $this->calculateSums($mainPositionIds);
    }

    public function getEarningSumsAttribute(): Collection
    {
        $mainPositionIds = $this->mainPositions()->where('type', 'BUDGET_TYPE_EARNING')->pluck('id');

        return $this->calculateSums($mainPositionIds);
    }

    public function getCommentedCostSumsAttribute(): Collection
    {
        $mainPositionIds = $this
            ->mainPositions()
            ->where('type', 'BUDGET_TYPE_COST')
            ->pluck('id');

        return $this->calculateCommentedSums($mainPositionIds);
    }

    public function getCommentedEarningSumsAttribute(): Collection
    {
        $mainPositionIds = $this
            ->mainPositions()
            ->where('type', 'BUDGET_TYPE_EARNING')
            ->pluck('id');

        return $this->calculateCommentedSums($mainPositionIds);
    }
}
