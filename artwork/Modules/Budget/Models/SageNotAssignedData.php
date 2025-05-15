<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\CollectiveBookings\CollectiveBooking;
use Artwork\Modules\Budget\Models\CollectiveBookings\IsCollectiveBooking;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $project_id
 * @property int $sage_id
 * @property int $tan
 * @property int $periode
 * @property string $kto_haben
 * @property string $kreditor
 * @property string $buchungstext
 * @property float $buchungsbetrag
 * @property string $belegnummer
 * @property string $belegdatum
 * @property string $kto_soll
 * @property string $sa_kto
 * @property string $kst_traeger
 * @property string $kst_stelle
 * @property string $buchungsdatum
 * @property bool $is_collective_booking
 * @property integer|null $parent_booking_id
 * @property-read SageNotAssignedData[]|Collection $children
 */
class SageNotAssignedData extends Model implements CollectiveBooking
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;
    use IsCollectiveBooking;

    protected $fillable = [
        'project_id',
        'sage_id',
        'tan',
        'periode',
        'kto_haben',
        'kreditor',
        'buchungstext',
        'buchungsbetrag',
        'belegnummer',
        'belegdatum',
        'kto_soll',
        'sa_kto',
        'kst_traeger',
        'kst_stelle',
        'buchungsdatum',
        'is_collective_booking',
        'parent_booking_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed();
    }
}
