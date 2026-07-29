<?php

namespace Artwork\Modules\Calendar\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Instanzweite Tagesbemerkung: genau ein Freitext pro Kalendertag, sichtbar in
 * Kalender, Planungskalender und Dienstplan (Keying über das Datum).
 *
 * @property int $id
 * @property Carbon $date
 * @property string $remark
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DayRemark extends Model
{
    use LogsActivity;

    protected $fillable = [
        'date',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id', 'createdBy');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id', 'updatedBy');
    }

    public function scopeBetweenDates(Builder $builder, Carbon|string $start, Carbon|string $end): Builder
    {
        return $builder
            ->where('date', '>=', Carbon::parse($start)->toDateString())
            ->where('date', '<=', Carbon::parse($end)->toDateString());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('day_remark')
            ->logOnly(['date', 'remark'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
