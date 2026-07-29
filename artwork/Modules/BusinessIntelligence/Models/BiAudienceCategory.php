<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\BusinessIntelligence\Enums\BiPricingTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mandantenweit konfigurierbare Besucher*innen-Kategorie
 * (z. B. Vollzahler*innen, Ermäßigt, Freikarten).
 */
class BiAudienceCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bi_audience_categories';

    protected $fillable = [
        'name',
        'pricing_type',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pricing_type' => BiPricingTypeEnum::class,
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(BiAudienceCategoryValue::class, 'bi_audience_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
