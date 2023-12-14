<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function cell()
    {
        return $this->belongsTo(ColumnCell::class);
    }
}
