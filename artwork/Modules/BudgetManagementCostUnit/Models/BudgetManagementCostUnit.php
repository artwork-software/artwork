<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetManagementCostUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cost_unit_number',
        'title'
    ];
}
