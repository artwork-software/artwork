<?php

namespace Artwork\Modules\MoneySourceCategoryMapping\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySourceCategory\Models\MoneySourceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneySourceCategoryMapping extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'money_source_id',
        'money_source_category_id'
    ];

    protected $table = 'money_source_category_mappings';

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }

    public function moneySourceCategory(): BelongsTo
    {
        return $this->belongsTo(MoneySourceCategory::class);
    }
}
