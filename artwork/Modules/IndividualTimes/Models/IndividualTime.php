<?php

namespace Artwork\Modules\IndividualTimes\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IndividualTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'full_day',
        'working_time_minutes',
        'series_uuid',
        'timeable_type',
        'timeable_id'
    ];

    protected $hidden = [
        'timeable_type', 'timeable_id'
    ];

    protected $appends = [
        'days_of_individual_time',
    ];

    protected $casts = [
        'full_day' => 'boolean',
        'working_time_minutes' => 'integer',
        'start_time' => TimeWithoutSeconds::class,
        'end_time' => TimeWithoutSeconds::class,
    ];

    public function timeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeIndividualByDateRange(Builder $builder, string $startDate, string $endDate): Builder
    {
        return $builder->where(function ($query) use ($startDate, $endDate): void {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($query) use ($startDate, $endDate): void {
                    // Handles cases where the event starts before and ends after the given range
                    $query->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * @return string<mixed>
     * @throws \DateMalformedPeriodStringException
     * @throws \DateMalformedStringException
     */
    public function getDaysOfIndividualTimeAttribute(): array
    {
        $startDate = new \DateTime($this->start_date);
        $endDate = new \DateTime($this->end_date);

        // Create a date interval of 1 day
        $interval = new \DateInterval('P1D');

        // Add one day to the end date because DatePeriod excludes the end date
        $endDate->modify('+1 day');

        // Create a DatePeriod instance to iterate over the date range
        $period = new \DatePeriod($startDate, $interval, $endDate);

        // Collect each date as a string (Y-m-d format) in the result array
        $days = [];
        foreach ($period as $date) {
            $days[] = $date->format('Y-m-d');
        }

        return $days;
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(
            IndividualTimeSeries::class,
            'series_uuid',
            'uuid',
            'series'
        );
    }
}
