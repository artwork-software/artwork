<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;

class TableColumnOrder extends Model
{
    protected $fillable = [
        'display_text',
        'position'
    ];
}
