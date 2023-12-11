<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $main_position_id
 * @property int $requested_by
 * @property int $requested
 * @property string $created_at
 * @property string $updated_at
 */
class MainPositionVerified extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_position_id',
        'requested_by',
        'requested'
    ];

    public function main_position(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }
}
