<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiTimeEffort extends Model
{
    use HasFactory;

    protected $table = 'bi_time_efforts';

    protected $fillable = [
        'project_id',
        'label',
        'effort_bucket',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'effort_bucket' => BiEffortBucketEnum::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}
