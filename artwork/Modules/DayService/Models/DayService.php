<?php

namespace Artwork\Modules\DayService\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
* @property int $id
 * @property string $name
 * @property string $icon
 * @property string $hex_color
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DayService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'hex_color',
    ];
}
