<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sub_position_id
 * @property int $position
 * @property bool $commented
 * @property string $created_at
 * @property string $updated_at
 */
class SubPositionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_position_id',
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
        return $this->belongsToMany(Column::class)->withTimestamps();
    }

    public function cells(): HasMany
    {
        return $this->hasMany(ColumnCell::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RowComment::class);
    }
}
