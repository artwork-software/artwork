<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sage_booking_log_id
 * @property string $action
 * @property string $target_type
 * @property int|null $sage_record_id
 * @property int|null $sage_id
 * @property float|null $buchungsbetrag
 * @property string|null $kst_traeger
 * @property bool $is_collective_booking
 */
class SageBookingLogEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'sage_booking_log_id',
        'action',
        'target_type',
        'sage_record_id',
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
        'project_id',
        'column_cell_id',
        'created_at',
    ];

    protected $casts = [
        'buchungsbetrag' => 'decimal:2',
        'is_collective_booking' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(SageBookingLog::class, 'sage_booking_log_id', 'id', 'log');
    }
}
