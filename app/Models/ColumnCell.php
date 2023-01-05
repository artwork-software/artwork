<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColumnCell extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_id',
        'sub_position_row_id',
        'value',
        'linked_money_source_id',
        'linked_type',
        'calculations'
    ];

    protected $primaryKey = 'id';

    protected $table = 'column_sub_position_row';


    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CellComments::class, 'cell_id', 'column_id');
    }
}
