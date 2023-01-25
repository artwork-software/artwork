<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use function Clue\StreamFilter\fun;

class SubPositionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'commented',
        'position'
    ];

    protected $casts = [
        'commented' => 'boolean'
    ];

    public function subPosition(): BelongsTo
    {
        return $this->belongsTo(SubPosition::class);
    }

    public function columns(): BelongsToMany
    {
        return $this->belongsToMany(Column::class)
            ->withTimestamps();
    }

    public function cells(){
        return $this->hasMany(ColumnCell::class);
    }

}


/**
 * select `columns`.*, `column_sub_position_row`.`sub_position_row_id` as `pivot_sub_position_row_id`, `column_sub_position_row`.`column_id` as `pivot_column_id`, `column_sub_position_row`.`value` as `pivot_value`, `column_sub_position_row`.`linked_money_source_id` as `pivot_linked_money_source_id`, `column_sub_position_row`.`id` as `pivot_id`, `column_sub_position_row`.`calculations` as `pivot_calculations`, `column_sub_position_row`.`comments` as `pivot_comments`, `column_sub_position_row`.`created_at` as `pivot_created_at`, `column_sub_position_row`.`updated_at` as `pivot_updated_at` from `columns` inner join `column_sub_position_row` on `columns`.`id` = `column_sub_position_row`.`column_id` where `column_sub_position_row`.`sub_position_row_id` in (1, 2, 3)
 */
