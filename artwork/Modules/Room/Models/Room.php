<?php

namespace Artwork\Modules\Room\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Models\BelongsToArea;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Artwork\Modules\RoomRoomAttributeMapping\Models\RoomRoomAttributeMapping;
use Artwork\Modules\RoomRoomCategoryMapping\Models\RoomRoomCategoryMapping;
use Artwork\Modules\User\Models\Traits\BelongsToUser;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $order
 * @property bool $temporary
 * @property bool $everyone_can_book
 * @property bool $fallback_room
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $area_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon $deleted_at
 * @property Area $area
 * @property User $creator
 * @property Collection<User> $room_admins
 * @property Collection<RoomFile> $room_files
 * @property Collection<Event> $events
 * @property Collection<Room> $adjoining_rooms
 * @property Collection<Room> $main_rooms
 * @property Collection<RoomCategory> $categories
 * @property Collection<RoomAttribute> $attributes
 * @property Collection<User> $users
 * @property Collection<User> $admins
 *
 *
 *
 *
 */
class Room extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;
    use HasChangesHistory;
    use BelongsToArea;
    use BelongsToUser;

    protected $fillable = [
        'name',
        'description',
        'temporary',
        'start_date',
        'end_date',
        'area_id',
        'user_id',
        'order',
        'everyone_can_book',
        'position',
        'created_at'
    ];

    protected $with = [
        'admins',
        'creator'
    ];

    protected $casts = [
        'everyone_can_book' => 'boolean',
        'fallback_type' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'temporary' => 'boolean'
    ];


    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
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
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed()
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

    public function scopeWithAudience(Builder $query, int $projectId): Builder
    {
        return $query->whereRelation('events', 'audience', '=', true)
            ->whereRelation('events', 'project_id', '=', $projectId);
    }

    public function scopeUnlessRoomIds(
        Builder $builder,
        ?array $roomIds
    ): Builder {
        return $builder->unless(
            is_null($roomIds),
            fn(Builder $builder) => $builder->whereIn('id', $roomIds)
        );
    }

    public function scopeUnlessRoomAttributeIds(
        Builder $builder,
        ?array $roomAttributeIds
    ): Builder {
        return $builder->unless(
            is_null($roomAttributeIds),
            fn(Builder $builder) => $builder->whereHas(
                'attributes',
                function ($query) use ($roomAttributeIds): void {
                    $query->whereIn('room_attributes.id', $roomAttributeIds);
                }
            )
        );
    }

    public function scopeUnlessAreaIds(
        Builder $builder,
        ?array $areaIds
    ): Builder {
        return $builder->unless(
            is_null($areaIds),
            fn(Builder $builder) => $builder->whereIn('area_id', $areaIds)
        );
    }

    public function scopeUnlessRoomCategoryIds(
        Builder $builder,
        ?array $roomCategoryIds
    ): Builder {
        return $builder->unless(
            is_null($roomCategoryIds),
            fn(Builder $builder) => $builder->whereHas(
                'categories',
                function ($query) use ($roomCategoryIds): void {
                    $query->whereIn('room_categories.id', $roomCategoryIds);
                }
            )
        );
    }

    public function scopeWhenFilterAdjoiningWithStartAndEndDate(
        Builder $builder,
        ?bool $adjoiningNotLoud,
        ?bool $adjoiningNoAudience,
        Carbon $startDate,
        Carbon $endDate
    ): Builder {
        return $builder->when(
            ($adjoiningNotLoud || $adjoiningNoAudience),
            function (Builder $builder) use ($adjoiningNotLoud, $adjoiningNoAudience, $startDate, $endDate): void {
                $builder->whereRelation(
                    'adjoining_rooms',
                    function (Builder $builder) use (
                        $adjoiningNoAudience,
                        $adjoiningNotLoud,
                        $startDate,
                        $endDate
                    ): void {
                        $builder->whereRelation(
                            'events',
                            function (Builder $builder) use (
                                $adjoiningNoAudience,
                                $adjoiningNotLoud,
                                $startDate,
                                $endDate
                            ): void {
                                $builder->when(
                                    ($startDate && $endDate),
                                    fn(Builder $builder) => $builder->startAndEndTimeOverlap($startDate, $endDate)
                                )
                                    ->when(
                                        $adjoiningNotLoud,
                                        fn(Builder $builder) => $builder->where(
                                            'events.is_loud',
                                            false
                                        )
                                    )
                                    ->when(
                                        $adjoiningNoAudience,
                                        fn(Builder $builder) => $builder->where(
                                            'events.audience',
                                            false
                                        )
                                    );
                            }
                        )->orWhereDoesntHave('events');
                    }
                )->orWhereDoesntHave('adjoining_rooms');
            }
        )->orderBy('position');
    }

    public function scopeNotIdIn(Builder $builder, array $ids): Builder
    {
        return $builder->whereNotIn('id', $ids);
    }
}
