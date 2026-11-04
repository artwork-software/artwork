<?php

namespace Artwork\Modules\IndividualTimes\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'break_minutes',
        'series_uuid',
        'timeable_type',
        'timeable_id',
        'in_workflow',
        'is_committed',
        'current_request_id',
        'committing_user_id',
    ];

    /**
     * Inhaltliche Felder: Ändert sich eines davon an einer freigegebenen Zeit, verfällt die Freigabe
     * (sie muss neu angefragt werden). In einer offenen Anfrage bleibt die Zeit; die Prüfenden sehen
     * dann den aktuellen Stand.
     */
    public const CONTENT_FIELDS = [
        'title',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'full_day',
        'working_time_minutes',
        'break_minutes',
        'timeable_type',
        'timeable_id',
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
        'break_minutes' => 'integer',
        'in_workflow' => 'boolean',
        'is_committed' => 'boolean',
        'start_time' => TimeWithoutSeconds::class,
        'end_time' => TimeWithoutSeconds::class,
    ];

    protected static function booted(): void
    {
        static::updating(function (IndividualTime $individualTime): void {
            if (
                $individualTime->getOriginal('is_committed')
                && !$individualTime->isDirty('is_committed')
                && $individualTime->isDirty(self::CONTENT_FIELDS)
            ) {
                $individualTime->is_committed = false;
                $individualTime->committing_user_id = null;
            }
        });
    }

    /**
     * Status in der Dienstplan-Freigabe: 'committed' (freigegeben), 'requested' (in offener Anfrage)
     * oder null (nicht Teil einer Freigabe).
     */
    public function workflowStatus(): ?string
    {
        if ($this->is_committed) {
            return 'committed';
        }

        return $this->in_workflow ? 'requested' : null;
    }

    /**
     * @return BelongsTo<ShiftPlanRequest, $this>
     */
    public function currentRequest(): BelongsTo
    {
        return $this->belongsTo(ShiftPlanRequest::class, 'current_request_id');
    }

    /**
     * Alle Freigabe-Anfragen, zu denen die Zeit gehörte (Verlauf).
     *
     * @return BelongsToMany<ShiftPlanRequest, $this>
     */
    public function shiftPlanRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            ShiftPlanRequest::class,
            'shift_plan_request_individual_times',
            'individual_time_id',
            'shift_plan_request_id'
        )->withTimestamps();
    }

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

    /**
     * @return BelongsTo<IndividualTimeSeries, $this>
     */
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
