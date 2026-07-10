<?php

namespace Artwork\Modules\Availability\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $available_type
 * @property int $available_id
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
class Availability extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'availabilities';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('availability')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'start_time',
        'end_time',
        'date',
        'full_day',
        'day_part',
        'comment',
        'is_series',
        'series_id',
        'available_type',
        'available_id',
    ];

    protected $casts = [
        'full_day' => 'boolean',
        'is_series' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'date_casted', 'has_conflicts', 'formatted_date'
    ];


    public function availabilities(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFormattedDateAttribute()
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('d.m.Y');
    }

    public function getDateCastedAttribute()
    {
        Carbon::setLocale('de');
        return Carbon::parse($this->date)->translatedFormat('D, d.m.Y');
    }

    protected $with = [
        'series', 'conflicts'
    ];

    public function getHasConflictsAttribute(): bool
    {
        return $this->relationLoaded('conflicts')
            ? $this->conflicts->isNotEmpty()
            : $this->conflicts()->exists();
    }

    public function series(): HasOne
    {
        return $this->hasOne(AvailabilitySeries::class, 'id', 'series_id');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(AvailabilitiesConflict::class, 'availability_id', 'id');
    }

    public function scopeBetweenDates(
        Builder $builder,
        Carbon $startDate,
        Carbon $endDate
    ): Builder {
        return $builder->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByDate(Builder $builder, Carbon $date): Builder
    {
        return $builder->where('date', $date);
    }

    public function scopeOrderedByDate(Builder $builder, string $direction = 'ASC'): Builder
    {
        return $builder->orderBy('date', $direction);
    }

    /**
     * Reichert eine (z.B. monatsgefilterte) Collection um die echten Datumsgrenzen ihrer Serien an
     * (series_start_date/series_end_date über ALLE Serientage, auch außerhalb des Filters), damit
     * das Frontend Serien als einen Zeitraum-Eintrag anzeigen und korrekt bearbeiten kann.
     *
     * @param \Illuminate\Support\Collection<int, self> $availabilities
     */
    public static function attachSeriesDateBounds(\Illuminate\Support\Collection $availabilities): void
    {
        $seriesIds = $availabilities->pluck('series_id')->filter()->unique()->values();
        if ($seriesIds->isEmpty()) {
            return;
        }

        $bounds = self::query()
            ->whereIn('series_id', $seriesIds)
            ->groupBy('series_id')
            ->selectRaw('series_id, MIN(date) as min_date, MAX(date) as max_date')
            ->get()
            ->keyBy('series_id');

        foreach ($availabilities as $availability) {
            $bound = $availability->series_id !== null ? $bounds->get($availability->series_id) : null;
            if ($bound !== null) {
                $availability->setAttribute('series_start_date', $bound->getAttribute('min_date'));
                $availability->setAttribute('series_end_date', $bound->getAttribute('max_date'));
            }
        }
    }
}
