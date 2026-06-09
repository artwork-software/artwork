<?php

namespace Artwork\Modules\Holidays\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $date
 * @property Carbon $end_date
 * @property int|null $rota
 * @property string|null $country
 * @property Subdivision[]|Collection $subdivisions
 * @property string|null $remote_identifier
 * @property bool $from_api
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = [
        'name',
        'date',
        'end_date',
        'rota',
        'country',
        'remote_identifier',
        'from_api',
        'yearly',
        'color',
        'treatAsSpecialDay',
    ];

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'from_api' => 'boolean',
        'yearly' => 'boolean',
        'treatAsSpecialDay' => 'boolean',
    ];

    protected $appends = [
        'casted_date',
    ];

    public function subdivisions(): BelongsToMany
    {
        return $this->belongsToMany(
            Subdivision::class,
            'holidays_subdivisions',
            'holiday_id',
            'subdivision_id'
        );
    }

    /**
     * Checks whether the given date is marked as a "Sondertag" (treatAsSpecialDay = true).
     * Considers one-time holidays (exact date) and yearly recurring holidays (month-day match).
     */
    public static function isSpecialDay(Carbon|string $date): bool
    {
        $day = $date instanceof Carbon ? $date : Carbon::parse($date);
        $formattedDate = $day->toDateString();
        $monthDay = $day->format('m-d');

        return self::where(function ($query) use ($formattedDate, $monthDay): void {
            $query->where(function ($q) use ($formattedDate): void {
                $q->where('yearly', false)
                    ->whereDate('date', $formattedDate);
            })->orWhere(function ($q) use ($monthDay): void {
                $q->where('yearly', true)
                    ->whereRaw("DATE_FORMAT(date, '%m-%d') = ?", [$monthDay]);
            });
        })->where('treatAsSpecialDay', true)->exists();
    }

    /**
     * @return string[]
     */
    public function getCastedDateAttribute(): array
    {
        return [
            'date' => $this->date->translatedFormat('l, jS F Y'),
            'end_date' => $this->end_date->translatedFormat('l, jS F Y'),
        ];
    }
}
