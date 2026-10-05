<?php

namespace Artwork\Modules\Vacation\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vacation_id
 * @property int $shift_id
 * @property string $user_name
 * @property string|null $scheduler_source
 * @property string|null $scheduled_at
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string $created_at
 * @property string $updated_at
 */
class VacationConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacation_id',
        'shift_id',
        'user_name',
        'scheduler_source',
        'scheduled_at',
        'date',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'date_casted',
        'scheduled_at_casted'
    ];

    public function vacation(): BelongsTo
    {
        return $this->belongsTo(Vacation::class, 'vacation_id', 'id', 'vacations');
    }


    public function getDateCastedAttribute(): string
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('d.m.Y');
    }

    /**
     * Zeitpunkt der Zuweisung für den Konflikthinweis — ohne ihn ist nicht
     * erkennbar, wann die Einteilung entgegen dem Eintrag entstanden ist.
     */
    public function getScheduledAtCastedAttribute(): ?string
    {
        return $this->scheduled_at !== null
            ? Carbon::parse($this->scheduled_at)->translatedFormat('d.m.Y H:i')
            : null;
    }
}
