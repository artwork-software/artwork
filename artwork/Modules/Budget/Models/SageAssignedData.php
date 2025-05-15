<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\CollectiveBookings\CollectiveBooking;
use Artwork\Modules\Budget\Models\CollectiveBookings\IsCollectiveBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $column_sub_position_row_id
 * @property int $sage_id
 * @property int $tan
 * @property int $periode
 * @property string $kto_haben
 * @property string $kreditor
 * @property string $buchungstext
 * @property float $buchungsbetrag
 * @property string $belegnummer
 * @property string $belegdatum
 * @property string $kto_soll
 * @property string $sa_kto
 * @property string $kst_traeger
 * @property string $kst_stelle
 * @property string $buchungsdatum
 * @property bool $is_collective_booking
 * @property integer|null $parent_booking_id
 * @property-read SageAssignedData[]|Collection $children
 */
class SageAssignedData extends Model implements CollectiveBooking
{
    use IsCollectiveBooking;

    protected $fillable = [
        'column_cell_id',
        'sage_id',
        'tan',
        'periode',
        'kto_haben',
        'kreditor',
        'buchungstext',
        'buchungsbetrag',
        'belegnummer',
        'belegdatum',
        'kto_soll',
        'sa_kto',
        'kst_traeger',
        'kst_stelle',
        'buchungsdatum',
        'is_collective_booking',
        'parent_booking_id',
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

    public function scopeBySageId(Builder $builder, int $sageId): Builder
    {
        return $builder->where('sage_id', $sageId);
    }
}
