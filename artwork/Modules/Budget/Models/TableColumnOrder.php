<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableColumnOrder extends Model
{
    use HasFactory;

    protected $table = 'table_column_orders';

    protected $fillable = [
        'display_text',
        'position'
    ];
}
