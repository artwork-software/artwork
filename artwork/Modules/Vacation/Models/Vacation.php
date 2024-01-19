<?php

namespace Artwork\Modules\Vacation\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Models\Freelancer;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $vacationer_type
 * @property int $vacationer_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property Carbon $date
 * @property boolean $full_day
 * @property string $comment
 * @property boolean $is_series
 * @property int $series_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Vacation extends Model
{
    use HasFactory;
    use HasChangesHistory;

    protected $table = 'vacations';

    protected $fillable = [
        'start_time',
        'end_time',
        'date',
        'full_day',
        'comment',
        'is_series',
        'series_id',
        'vacationer_type',
        'vacationer_id',
    ];

    protected $casts = [
        'full_day' => 'boolean',
        'is_series' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'date_casted'
    ];

    protected $with = [
        'series', 'conflicts'
    ];

    public function vacations(): MorphTo
    {
        return $this->morphTo();
    }

    public function series(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VacationSeries::class, 'id', 'series_id');
    }

    public function conflicts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VacationConflict::class, 'vacation_id', 'id');
    }

    public function getDateCastedAttribute()
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('D, d.m.Y');
    }

    public function getShiftConflictAttribute(): array
    {
        $shifts = $this->getCommittedShifts();

        //dd($shifts);
        $shiftsWithConflict = [];
        foreach ($shifts as $shift) {
            if ($this->isShiftInConflict($shift)) {
                $shiftsWithConflict[] = $shift;
            }
        }

        return $shiftsWithConflict;
    }

    private function getCommittedShifts(): \Illuminate\Database\Eloquent\Collection|array
    {
        $model = $this->vacationer_type === Freelancer::class ? Freelancer::class : User::class;

        return $model::find($this->vacationer_id)
            ->shifts()
            ->where('event_start_day', '<=', $this->date)
            ->where('event_end_day', '>=', $this->date)
            ->where('is_committed', true)
            ->without(['users', 'freelancer', 'service_provider', 'craft'])
            ->get();
    }

    private function isShiftInConflict($shift): bool
    {
        if ($this->full_day) {
            return $this->date === $shift->event_start_day;
        }

        $date = Carbon::parse($this->date);
        if (!$date->between($shift->event_start_day, $shift->event_end_day)) {
            return false;
        }
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $start->between($shift->start, $shift->end) ||
            $end->between($shift->start, $shift->end);
    }

}
