<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellCalculations extends Model
{
    use HasFactory;

    protected $fillable = [
        'cell_id',
        'name',
        'value',
        'description',
        'position'
    ];

    public function cell(): BelongsTo
    {
        return $this->belongsTo(ColumnCell::class, 'cell_id', 'id', 'cell');
    }
}
