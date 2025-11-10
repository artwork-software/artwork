<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $account_number
 * @property string $title
 */
class BudgetManagementAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_number',
        'title',
        'is_account_for_revenue'
    ];

    protected $casts = [
        'is_account_for_revenue'
    ];

    public function scopeByAccountNumberOrTitle(Builder $builder, string $search): Builder
    {
        $search = trim($search);

        // Sonderzeichen escapen, um LIKE sicher zu machen
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        return $builder->where(function (Builder $query) use ($escaped): void {
            $query->whereRaw('LOWER(account_number) LIKE ?', ['%' . strtolower($escaped) . '%'])
                ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($escaped) . '%']);
        });
    }

    public function scopeIsAccountForRevenue(Builder $builder, bool $isAccountForRevenue): Builder
    {
        return $builder->where('is_account_for_revenue', $isAccountForRevenue);
    }
}
