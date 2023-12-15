<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $sourceable_type
 * @property int $sourceable_id
 * @property int $money_source_id
 * @property string $linked_type
 * @property string $created_at
 * @property string $updated_at
 */
class SumMoneySource extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }
}
