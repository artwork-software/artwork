<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $main_position_id
 * @property int $column_id
 * @property Collection<SumComment> $comments
 * @property SumMoneySource|null $sumMoneySource
 * @property string $created_at
 * @property string $updated_at
 */
class MainPositionDetails extends Model
{
    use HasFactory;
    use BelongsToMainPosition;
    use SoftDeletes;

    protected $table = 'main_position_details';

    protected $fillable = [
        'main_position_id',
        'column_id'
    ];

    public function comments(): MorphMany
    {
        return $this->morphMany(SumComment::class, 'commentable');
    }

    public function sumMoneySource(): MorphOne
    {
        return $this->morphOne(SumMoneySource::class, 'sourceable');
    }
}
