<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sub_position_id
 * @property int $position
 * @property int $order
 * @property bool $commented
 * @property SubPosition $subPosition
 * @property Collection<ColumnCell> $cells
 * @property Collection<RowComment> $comments
 * @property string $created_at
 * @property string $updated_at
 */
class SubPositionRow extends Model
{
    use HasFactory;
    use BelongsToSubPosition;
    use SoftDeletes;

    protected $fillable = [
        'sub_position_id',
        'commented',
        'position',
        'order',
    ];

    protected $casts = [
        'commented' => 'boolean'
    ];

    public function cells(): HasMany
    {
        return $this->hasMany(ColumnCell::class, 'sub_position_row_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RowComment::class);
    }
}
