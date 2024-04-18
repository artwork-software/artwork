<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int $position
 * @property int $main_position_id
 * @property string $is_verified
 * @property MainPosition $mainPosition
 * @property SubPositionVerified|null $verified
 * @property Collection<SubPositionSumDetail> $subPositionSumDetails
 * @property Collection<SubPositionRow> $subPositionRows
 * @property string $created_at
 * @property string $updated_at
 * @property bool $is_fixed
 */
class SubPosition extends Model
{
    use HasFactory;
    use BelongsToMainPosition;
    use SoftDeletes;

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

    public function subPositionRows(): HasMany
    {
        return $this->hasMany(SubPositionRow::class);
    }

    public function verified(): HasOne
    {
        return $this->hasOne(SubPositionVerified::class);
    }

    public function subPositionSumDetails(): HasMany
    {
        return $this->hasMany(SubPositionSumDetail::class);
    }

    public function getColumnSumsAttribute(): \Illuminate\Support\Collection
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
            ->mapWithKeys(fn (Collection $cells, $column_id) => [
                $column_id => [
                    //replace , with . to cast properly to float
                    'sum' => $cells->sum(
                        fn (ColumnCell $columnCell) => floatval(str_replace(',', '.', $columnCell->value))
                    ),
                    'hasComments' => isset($sumDetails[$column_id]) &&
                        $sumDetails[$column_id]->comments_count > 0,
                    'hasMoneySource' => isset($sumDetails[$column_id]) &&
                        $sumDetails[$column_id]->sum_money_source_count > 0,
                ]
            ]);
    }

    public function groupedSumDetails(): Collection
    {
        return $this->subPositionSumDetails()
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->keyBy('column_id');
    }
}
