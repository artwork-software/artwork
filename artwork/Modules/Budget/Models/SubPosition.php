<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $position
 * @property int $main_position_id
 * @property string $is_verified
 * @property boolean $is_fixed
 */
class SubPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_position_id',
        'name',
        'position',
        'is_verified',
        'is_fixed'
    ];

    protected $casts = [
        'is_fixed' => 'boolean',
    ];

    protected $appends = ['columnSums'];

    public function mainPosition(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }

    public function subPositionRows(): HasMany
    {
        return $this->hasMany(SubPositionRow::class);
    }

    public function getColumnSumsAttribute()
    {
        $subPositionRowIds = $this->subPositionRows()
            ->where('commented', false)
            ->pluck('id');
        $sumDetails = $this->groupedSumDetails();


        // @Jakob hier bitte checken
        // Siehe Notion
        return ColumnCell::query()
            ->whereRelation('column', 'commented', false)
            ->where('commented', false)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->get()
            ->groupBy('column_id')
            ->skip(3)
            ->mapWithKeys(fn ($cells, $column_id) => [
                $column_id => [
                    'sum' => $cells->sum('value'),
                    'hasComments' => @$sumDetails[$column_id]->comments_count > 0,
                    'hasMoneySource' => @$sumDetails[$column_id]->sum_money_source_count > 0,
                ]
            ]);
    }

    public function verified()
    {
        return $this->hasOne(SubPositionVerified::class);
    }

    public function groupedSumDetails(): Collection
    {
        return $this->subPositionSumDetails()
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->keyBy('column_id');
    }

    public function subPositionSumDetails(): HasMany
    {
        return $this->hasMany(SubpositionSumDetail::class);
    }


}
