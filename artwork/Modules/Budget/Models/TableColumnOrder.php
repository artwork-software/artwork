<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;

class TableColumnOrder extends Model
{
    protected $table = 'table_column_orders';

    protected $fillable = [
        'display_text',
        'position'
    ];
}
