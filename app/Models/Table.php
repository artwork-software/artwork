<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'commentedCostSums',
        'commentedEarningSums',
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function columns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Column::class, 'table_id', 'id');
    }

    public function mainPositions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MainPosition::class, 'table_id', 'id');
    }

    protected function calculateSums($mainPositionIds) {
        $subPositionIds =  SubPosition::query()
            ->whereIntegerInRaw('main_position_id', $mainPositionIds)
            ->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        return ColumnCell::query()
            ->where('commented', true)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->get()
            ->groupBy('column_id')
            ->mapWithKeys(function ($cells, $column_id) {
                return [ $column_id => $cells->sum('value')];
            });
    }

    public function getCommentedCostSumsAttribute()
    {
        $mainPositionIds = $this->mainPositions()->where('type', 'BUDGET_TYPE_COST')->pluck('id');

        return $this->calculateSums($mainPositionIds);
    }

    public function getCommentedEarningSumsAttribute()
    {
        $mainPositionIds = $this->mainPositions()->where('type', 'BUDGET_TYPE_EARNING')->pluck('id');

        return $this->calculateSums($mainPositionIds);
    }

}
