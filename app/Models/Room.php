<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $order
 * @property bool $temporary
 * @property bool $everyone_can_book
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $area_id
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Room extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;
    use HasChangesHistory;

    protected $fillable = [
        'name',
        'description',
        'temporary',
        'start_date',
        'end_date',
        'area_id',
        'user_id',
        'order',
        'everyone_can_book'
    ];

    protected $with = [
      'admins'
    ];

    protected $casts = [
        'everyone_can_book' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'temporary' => 'boolean'
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id')
            ->withPivot('is_admin', 'can_request');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id')
            ->wherePivot('is_admin', true);
    }

    //@todo: fix phpcs error - refactor function name to roomFiles
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function room_files(): HasMany
    {
        return $this->hasMany(RoomFile::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    //@todo: fix phpcs error - refactor function name to adjoiningRooms
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function adjoining_rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'adjoining_room_main_room', 'main_room_id', 'adjoining_room_id');
    }

    //@todo: fix phpcs error - refactor function name to mainRooms
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function main_rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'adjoining_room_main_room', 'adjoining_room_id', 'main_room_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(RoomCategory::class)->using(RoomRoomCategoryMapping::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(RoomAttribute::class)->using(RoomRoomAttributeMapping::class);
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())
            ->orWhere('temporary', true)
            ->where('end_date', '<=', now());
    }

    public function pruning(): int
    {
        return $this->room_files()->delete();
    }

    public function getEventsAt(Carbon $dateTime): Collection
    {
        return $this->events->filter(
            fn (Event $event) => $dateTime->between(
                Carbon::parse($event->start_time),
                Carbon::parse($event->end_time)
            )
        );
    }

    public function scopeWithAudience(Builder $query): Builder
    {
        return $query->whereRelation('events', 'audience', '=', true);
    }
}
