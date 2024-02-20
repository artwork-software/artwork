<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $column_sub_position_row_id
 * @property int $sage_id
 * @property int $tan
 * @property string $kreditor
 * @property string $buchungstext
 * @property float $buchungsbetrag
 * @property string $belegnummer
 * @property string $belegdatum
 * @property string $sa_kto
 * @property string $kst_traeger
 * @property string $kst_stelle
 * @property string $buchungsdatum
 */
class SageAssignedData extends Model
{
    protected $fillable = [
        'column_cell_id',
        'sage_id',
        'tan',
        'kreditor',
        'buchungstext',
        'buchungsbetrag',
        'belegnummer',
        'belegdatum',
        'sa_kto',
        'kst_traeger',
        'kst_stelle',
        'buchungsdatum'
    ];

    public function columnCell(): BelongsTo
    {
        return $this->belongsTo(
            ColumnCell::class,
            'column_cell_id',
            'id',
            'column_sub_position_row'
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(
            SageAssignedDataComment::class,
            'sage_assigned_data_id',
            'id'
        );
    }

    public function scopeByColumnCellId(Builder $builder, int $columnCellId): Builder
    {
        return $builder->where('column_cell_id', $columnCellId);
    }
}
