<?php

namespace Artwork\Modules\BudgetManagementAccount\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetManagementAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_number',
        'title'
    ];

    public function scopeByAccountNumberOrTitle(Builder $builder, string $search): Builder
    {
        return $builder
            ->where('account_number', 'like', $search . '%')
            ->orWhere('title', 'like', $search . '%');
    }
}
