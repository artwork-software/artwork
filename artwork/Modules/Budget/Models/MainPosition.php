<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $table_id
 * @property string $type
 * @property int $position
 * @property string $name
 * @property string $is_verified
 * @property Table $table
 * @property Collection<SubPosition> $subPositions
 * @property MainPositionVerified|null $verified
 * @property Collection<MainPositionDetails> $mainPositionSumDetails
 * @property string $created_at
 * @property string $updated_at
 * @property bool $is_fixed
 */
class MainPosition extends Model
{
    use HasFactory;
    use BelongsToTable;
    use SoftDeletes;

    protected $fillable = [
        'table_id',
        'type',
        'name',
        'position',
        'is_verified',
        'is_fixed'
    ];

    protected $casts = [
        'is_fixed' => 'boolean',
    ];

    protected $appends = ['columnSums', 'columnVerifiedChanges'];

    public function subPositions(): HasMany
    {
        return $this->hasMany(SubPosition::class);
    }

    public function verified(): HasOne
    {
        return $this->hasOne(MainPositionVerified::class);
    }

    public function mainPositionSumDetails(): HasMany
    {
        return $this->hasMany(MainPositionDetails::class);
    }

    public function getColumnSumsAttribute()
    {
        $subPositionIds = $this->subPositions()->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->where('commented', false)
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        $sumDetails = $this->groupedSumDetails();

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
                        'hasComments' => isset($sumDetails[$column_id]) && $sumDetails[$column_id]->comments_count > 0,
                        'hasMoneySource' => isset($sumDetails[$column_id]) &&
                            $sumDetails[$column_id]->sum_money_source_count > 0,
                    ]
                ]);
    }

    public function getColumnVerifiedChangesAttribute()
    {
        return ColumnCell::whereIn(
            'sub_position_row_id',
            SubPositionRow::whereIn('sub_position_id', $this->subPositions()->pluck('id'))->pluck('id')
        )->whereColumn('verified_value', '!=', 'value')->where('verified_value', '!=', '')->exists();
    }

    public function groupedSumDetails(): Collection
    {
        return $this->mainPositionSumDetails()
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->keyBy('column_id');
    }
}
