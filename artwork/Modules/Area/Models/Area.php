<?php

namespace Artwork\Modules\Area\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    public static function booting(): void
    {
        parent::booting();
        static::deleting(static fn(IlluminateModel $model) => $model->rooms()->delete());
        static::restoring(
            static fn(IlluminateModel $model) => $model->trashedRooms()->each(fn($room) => $room->restore())
        );
        static::forceDeleting(static fn(IlluminateModel $model) => $model->rooms()->forceDelete());
    }

    protected $fillable = [
        'name'
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function trashedRooms(): HasMany
    {
        return $this->rooms()->onlyTrashed();
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed();
    }
}
