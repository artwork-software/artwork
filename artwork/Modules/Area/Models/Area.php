<?php
namespace Artwork\Modules\Area\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 */
class Area extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'name'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function trashed_rooms()
    {
        return $this->rooms()->onlyTrashed();
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subMonth());
    }
}
