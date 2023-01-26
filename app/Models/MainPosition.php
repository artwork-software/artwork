<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function Clue\StreamFilter\fun;

class MainPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'position',
        'is_verified'
    ];

    protected $appends = ['columnSums'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subPositions(): HasMany
    {
        return $this->hasMany(SubPosition::class);
    }

    public function getColumnSumsAttribute()
    {
        $subPositionIds = $this->subPositions()->pluck('id');

        $subPositionRowIds = SubPositionRow::query()
            ->whereIntegerInRaw('sub_position_id', $subPositionIds)
            ->pluck('id');

        return ColumnCell::query()
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
}
