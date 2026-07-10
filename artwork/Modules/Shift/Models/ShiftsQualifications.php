<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\QueryBuilders\ShiftsQualificationsBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $shift_id
 * @property int $shift_qualification_id
 * @property int $value
 * @property int $overbooked_value
 */
class ShiftsQualifications extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Transient: wird von ShiftService::delete gesetzt, wenn der Schichtplatz nur
     * als Teil der Schicht-Löschkaskade verschwindet — der Observer schreibt dann
     * keinen eigenen "Schichtplatz entfernt"-Verlaufseintrag (die Schicht selbst
     * bekommt ihren Lösch-Eintrag).
     */
    public bool $deletingViaShiftCascade = false;

    protected $fillable = [
        'shift_id',
        'shift_qualification_id',
        'value',
        'overbooked_value'
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(
            Shift::class,
            'shift_id',
            'id',
            'shifts'
        );
    }

    public function shiftQualification(): BelongsTo
    {
        return $this->belongsTo(
            ShiftQualification::class,
            'shift_qualification_id', // FK auf DIESER Tabelle
            'id',                      // PK auf shift_qualifications
            'shiftQualifications'      // Relation Name
        );
    }

    public function newEloquentBuilder($query): ShiftsQualificationsBuilder
    {
        return new ShiftsQualificationsBuilder($query);
    }

    public function scopeByShiftIdAndShiftQualificationId(
        Builder $builder,
        int $shiftId,
        int $shiftQualificationId
    ): Builder {
        return $builder
            ->where('shift_id', $shiftId)
            ->where('shift_qualification_id', $shiftQualificationId);
    }
}
