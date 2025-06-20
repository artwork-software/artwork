<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;

class BudgetColumnSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_name'
    ];

    public function scopeByColumnPosition(Builder $builder, int $position): Builder
    {
        return $builder->where('column_position', $position);
    }
}
