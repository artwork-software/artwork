<?php

namespace Artwork\Modules\MoneySourceCategory\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySourceCategoryMapping\Models\MoneySourceCategoryMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
