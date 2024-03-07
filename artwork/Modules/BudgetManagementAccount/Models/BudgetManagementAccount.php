<?php

namespace Artwork\Modules\BudgetManagementAccount\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetManagementAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_number',
        'title'
    ];
}
