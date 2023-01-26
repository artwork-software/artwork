<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'is_verified'
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
        $subPositionRowIds = $this->subPositionRows()->pluck('id');

        return ColumnCell::query()
                ->whereIntegerInRaw('sub_position_row_id', $subPositionRowIds)
                ->get()
                ->groupBy('column_id')
                ->skip(3)
                ->mapWithKeys(function ($cells, $column_id) {
                    return [ $column_id => $cells->sum('value')];
                });
    }

    public function verified(){
        return $this->hasOne(SubPositionVerified::class);
    }


}
