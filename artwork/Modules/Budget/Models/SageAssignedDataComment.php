<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\Traits\BelongsToUser;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $sage_assigned_data_id
 * @property string $comment
 */
class SageAssignedDataComment extends Model
{
    use HasFactory;
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'sage_assigned_data_id',
        'comment'
    ];

    public function sageAssignedData(): BelongsTo
    {
        return $this->belongsTo(
            SageAssignedData::class,
            'sage_assigned_data_id',
            'id',
            'sage_assigned_data'
        );
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('d.m.Y H:i:s');
    }
}
