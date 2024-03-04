<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $type
 * @property int $column_id
 * @property Collection<SumComment> $comments
 * @property SumMoneySource|null $sumMoneySource
 * @property string $created_at
 * @property string $updated_at
 */
class BudgetSumDetails extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
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

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }
}
