<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cell_id
 * @property string $name
 * @property int $value
 * @property string $description
 * @property int $position
 * @property string $created_at
 * @property string $updated_at
 */
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
