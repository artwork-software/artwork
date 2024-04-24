<?php

namespace Artwork\Modules\FreelancerVacation\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $freelancer_id
 * @property string $from
 * @property string $until
 * @property string $created_at
 * @property string $updated_at
 */
class FreelancerVacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'from',
        'until'
    ];

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(
            Freelancer::class,
            'freelancer_id',
            'id',
            'freelancers'
        );
    }
}
