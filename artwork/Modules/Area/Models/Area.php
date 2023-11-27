<?php
namespace Artwork\Modules\Area\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property-read Room[]|Collection $rooms
 */
class Area extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    public static function booting(): void
    {
        parent::booting();
        static::deleting(static fn(IlluminateModel $model) => $model->rooms()->delete());
    }

    protected $fillable = [
        'name'
    ];

    public function rooms(): HasMany
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
