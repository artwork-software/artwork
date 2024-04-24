<?php

namespace Artwork\Modules\Scheduling\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property int $count
 * @property int $user_id
 * @property string $type
 * @property int $model_id
 * @property string $created_at
 * @property string $updated_at
 */
class Scheduling extends Model
{
    use HasFactory;

    protected $fillable = [
        'count',
        'user_id',
        'type',
        'model',
        'model_id',
    ];

    public function scopeByUserId(Builder $builder, int $userId): Builder
    {
        return $builder->where('user_id', $userId);
    }

    public function scopeByType(Builder $builder, string $type): Builder
    {
        return $builder->where('type', $type);
    }

    public function scopeByModel(Builder $builder, string $model): Builder
    {
        return $builder->where('model', $model);
    }

    public function scopeByModelId(Builder $builder, int $modelId): Builder
    {
        return $builder->where('model_id', $modelId);
    }

    public function scopeByUpdatedAtLowerOrEqualThan(Builder $builder, Carbon $carbon): Builder
    {
        return $builder->where('updated_at', '<=', $carbon);
    }
}
