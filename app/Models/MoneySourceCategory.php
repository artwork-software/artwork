<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MoneySourceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function moneySources(): BelongsToMany
    {
        return $this
            ->belongsToMany(MoneySource::class, 'money_source_category_mappings')
            ->using(MoneySourceCategoryMapping::class);
    }
}
