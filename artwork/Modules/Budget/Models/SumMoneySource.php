<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    protected $fillable = [
        'sourceable_type',
        'sourceable_id',
        'money_source_id',
        'linked_type'
    ];

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }
}
