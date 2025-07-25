<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $cost_unit_number
 * @property string $title
 */
class BudgetManagementCostUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cost_unit_number',
        'title'
    ];

    public function scopeByCostUnitNumberOrTitle(Builder $builder, string $search): Builder
    {
        return $builder
            ->where('cost_unit_number', 'like', $search . '%')
            ->orWhere('title', 'like', $search . '%');
    }
}
