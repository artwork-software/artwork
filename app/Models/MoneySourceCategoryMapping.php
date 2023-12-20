<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
