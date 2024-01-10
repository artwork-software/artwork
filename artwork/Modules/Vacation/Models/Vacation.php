<?php

namespace Artwork\Modules\Vacation\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $vacationer_type
 * @property int $vacationer_id
 * @property Carbon $from
 * @property Carbon $until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Vacationer $vacationer
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
        'is_serie',
        'serie_id',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time'
    ];

    public function vacations(): MorphTo
    {
        return $this->morphTo();
    }
}
