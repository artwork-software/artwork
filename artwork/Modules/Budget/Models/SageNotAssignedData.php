<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $project_id
 * @property int $sage_id
 * @property int $tan
 * @property string $kreditor
 * @property string $buchungstext
 * @property float $buchungsbetrag
 * @property string $belegnummer
 * @property string $belegdatum
 * @property string $sa_kto
 * @property string $kst_traeger
 * @property string $kst_stelle
 * @property string $buchungsdatum
 */
class SageNotAssignedData extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'project_id',
        'sage_id',
        'tan',
        'kreditor',
        'buchungstext',
        'buchungsbetrag',
        'belegnummer',
        'belegdatum',
        'sa_kto',
        'kst_traeger',
        'kst_stelle',
        'buchungsdatum'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays(30))->withTrashed();
    }
}
