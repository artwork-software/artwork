<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function Clue\StreamFilter\fun;

/**
 * @property int $id
 * @property int $project_id
 * @property string $type
 * @property int $position
 * @property string $name
 * @property string $is_verified
 * @property boolean $is_fixed
 */
class MainPosition extends Model
{
    use HasFactory;

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

    protected $appends = ['columnSums'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function subPositions(): HasMany
    {
        return $this->hasMany(SubPosition::class);
    }

    public function getColumnSumsAttribute()
    {
        $subPositionIds = $this->subPositions()->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->where('commented', false)
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        return ColumnCell::query()
            ->where('commented', false)
            ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
            ->get()
            ->groupBy('column_id')
            ->skip(3)
            ->mapWithKeys(function ($cells, $column_id) {
                return [ $column_id => $cells->sum('value')];
            });
    }

    public function verified(): HasOne
    {
        return $this->hasOne(MainPositionVerified::class);
    }

    public function mainPositionSumDetails(): HasMany
    {
        return $this->hasMany(MainPositionDetails::class);
    }
}
