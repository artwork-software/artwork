<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $column_id
 * @property int $sub_position_row_id
 * @property string $value
 * @property bool $commented
 * @property int $linked_money_source_id
 * @property string $linked_type
 * @property string $verified_value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ColumnCell extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_id',
        'sub_position_row_id',
        'value',
        'linked_money_source_id',
        'linked_type',
        'verified_value',
        'commented'
    ];

    protected $casts = [
        'commented' => 'boolean'
    ];

    protected $primaryKey = 'id';

    protected $table = 'column_sub_position_row';

    public function subPositionRows(): BelongsToMany
    {
        return $this->belongsToMany(SubPositionRow::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CellComment::class);
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(CellCalculations::class, 'cell_id');
    }
}
