<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColumnCell extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'column_id',
        'sub_position_row_id',
        'value',
        'linked_money_source_id',
        'linked_type',
        'verified_value',
        'commented'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'commented' => 'boolean'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table = 'column_sub_position_row';

    /**
     * @return BelongsToMany
     */
    public function subPositionRows(): BelongsToMany
    {
        return $this->belongsToMany(SubPositionRow::class);
    }

    /**
     * @return BelongsTo
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CellComment::class);
    }

    /**
     * @return HasMany
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(CellCalculations::class, 'cell_id');
    }
}
