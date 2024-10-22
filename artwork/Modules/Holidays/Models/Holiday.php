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

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'from_api' => 'boolean',
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
}
