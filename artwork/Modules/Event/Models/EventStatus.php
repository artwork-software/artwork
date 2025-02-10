<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $name
 * @property string $color
 * @property int $order
 * @property bool $default
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EventStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order', 'color', 'default'];

    protected $casts = [
        'default' => 'boolean',
    ];
}
