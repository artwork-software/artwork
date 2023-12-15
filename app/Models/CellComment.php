<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $column_cell_id
 * @property int $user_id
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class CellComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_cell_id',
        'user_id',
        'description'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
