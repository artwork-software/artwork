<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $column_id
 * @property int $sub_position_row_id
 * @property string $value
 * @property bool $commented
 * @property int $linked_money_source_id
 * @property string $linked_type
 * @property string $verified_value
 * @property Column $column
 * @property Collection<CellComment> $comments
 * @property Collection<CellCalculation> $calculations
 * @property Collection<SageAssignedData> $sageAssignedData
 * @property SubPositionRow $subPositionRow
 * @property string $created_at
 * @property string $updated_at
 */
class ColumnCell extends Model
{
    use HasFactory;
    use BelongsToColumn;
    use SoftDeletes;

    protected $table = 'column_sub_position_row';

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

    protected $appends = [
        'sage_value',
        'current_value',
    ];

    public function subPositionRow(): BelongsTo
    {
        return $this->belongsTo(
            SubPositionRow::class,
            'sub_position_row_id',
            'id',
            'sub_position_rows'
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CellComment::class, 'column_cell_id', 'id');
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(CellCalculation::class, 'cell_id', 'id');
    }

    public function sageAssignedData(): HasMany
    {
        return $this->hasMany(
            SageAssignedData::class,
            'column_cell_id',
            'id'
        );
    }

    public function getSageValueAttribute(): ?string
    {
        if ($this->sageAssignedData->isNotEmpty()) {
            return $this->sageAssignedData->sum('buchungsbetrag');
        }
        return null;
    }

    public function getCurrentValueAttribute(): ?string
    {
        return $this->value;
    }
}
