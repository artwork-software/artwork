<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property int $id
 * @property int $main_position_id
 * @property int $column_id
 * @property string $created_at
 * @property string $updated_at
 */
class MainPositionDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function comments(): MorphMany
    {
        return $this->morphMany(SumComment::class, 'commentable');
    }

    public function mainPosition(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }

    public function sumMoneySource(): MorphOne
    {
        return $this->morphOne(SumMoneySource::class, 'sourceable');
    }
}
