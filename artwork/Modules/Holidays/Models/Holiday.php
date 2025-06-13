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
