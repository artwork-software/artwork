<?php

namespace Artwork\Modules\Room\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Models\BelongsToArea;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\Room\Models\RoomRoomAttributeMapping;
use Artwork\Modules\Room\Models\RoomRoomCategoryMapping;
use Artwork\Modules\Shift\Models\Shift;
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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $order
 * @property bool $temporary
 * @property bool $everyone_can_book
 * @property bool $fallback_room
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int|null $capacity
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
 */
class Room extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;
    use LogsActivity;
    use BelongsToArea;
    use BelongsToUser;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('room')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'color',
        'description',
        'temporary',
        'start_date',
        'end_date',
        'area_id',
        'user_id',
        'order',
        'everyone_can_book',
        'position',
        'created_at',
        'relevant_for_disposition',
        'capacity',
    ];

    protected $with = [
        'admins',
        'creator'
    ];

    protected static function booted(): void
    {
        // Raum-Lookup-Cache der Inventar-Artikel (je Prozess, unter Octane/Swoole
        // request-übergreifend) nach Umbenennung/Löschung leeren
        static::saved(static fn () => InventoryArticle::flushPropertyLookupCaches());
        static::deleted(static fn () => InventoryArticle::flushPropertyLookupCaches());
    }

    protected $casts = [
        'everyone_can_book' => 'boolean',
        'fallback_type' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'temporary' => 'boolean',
        'relevant_for_disposition' => 'boolean',
        'capacity' => 'integer',
    ];

    /**
     * Effektive Anzeigefarbe: eigene Farbe, sonst die des Areals (Standard Schwarz).
     * Setzt voraus, dass die area-Relation geladen ist, sonst lazy-load.
     */
    public function getEffectiveColor(): string
    {
        return $this->color ?? $this->area?->color ?? '#000000';
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class,
            'area_id',
            'id',
            'areas'
        );
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'room_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id')
            ->withPivot('is_admin', 'can_request');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id')
            ->wherePivot('is_admin', true);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function requestableBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id')
            ->wherePivot('can_request', true);
    }

    /**
     * Teams mit Raumzugriff (department_room): alle Mitglieder gelten wie direkt eingetragene Personen.
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_room')
            ->withPivot('is_admin', 'can_request')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function adminDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_room')
            ->wherePivot('is_admin', true);
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function requestableByDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_room')
            ->wherePivot('can_request', true);
    }

    /**
     * Räume, die die Person verwaltet – direkt (room_user.is_admin) oder über ein Admin-Team.
     * @param Builder<Room> $query
     */
    public function scopeAdministeredBy(Builder $query, int $userId): void
    {
        $query->where(function (Builder $query) use ($userId): void {
            $query->whereHas('admins', fn (Builder $q) => $q->where('users.id', $userId))
                ->orWhereHas('adminDepartments.users', fn (Builder $q) => $q->where('users.id', $userId));
        });
    }

    /**
     * Räume, in denen die Person Belegungen anfragen darf – direkt oder über ein Team.
     * @param Builder<Room> $query
     */
    public function scopeRequestableByUser(Builder $query, int $userId): void
    {
        $query->where(function (Builder $query) use ($userId): void {
            $query->whereHas('requestableBy', fn (Builder $q) => $q->where('users.id', $userId))
                ->orWhereHas('requestableByDepartments.users', fn (Builder $q) => $q->where('users.id', $userId));
        });
    }

    public function isAdministeredBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        if ($this->relationLoaded('admins') && $this->relationLoaded('adminDepartments')) {
            return in_array($userId, $this->effectiveAdminIds(), true);
        }

        return static::query()->whereKey($this->getKey())->administeredBy($userId)->exists();
    }

    public function isRequestableBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return static::query()->whereKey($this->getKey())->requestableByUser($userId)->exists();
    }

    /**
     * Hat der Raum überhaupt Raumadmins (Person oder Team mit Mitgliedern)? Ohne sie entscheidet die Person,
     * die den Raum angelegt hat.
     */
    public function hasAnyAdmin(): bool
    {
        return $this->admins()->exists() || $this->adminDepartments()->whereHas('users')->exists();
    }

    /**
     * IDs aller Raumadmins inkl. Team-Mitgliedern (Payloads ans Frontend, Benachrichtigungen).
     * @return array<int, int>
     */
    public function effectiveAdminIds(): array
    {
        $this->loadMissing(['admins:id', 'adminDepartments.users:id']);

        return $this->admins->pluck('id')
            ->merge($this->adminDepartments->flatMap(fn (Department $department) => $department->users->pluck('id')))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * IDs aller Anfrageberechtigten inkl. Team-Mitgliedern.
     * @return array<int, int>
     */
    public function effectiveRequestableByIds(): array
    {
        $this->loadMissing(['requestableBy:id', 'requestableByDepartments.users:id']);

        return $this->requestableBy->pluck('id')
            ->merge($this->requestableByDepartments->flatMap(
                fn (Department $department) => $department->users->pluck('id')
            ))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Raumadmins inkl. Team-Mitgliedern aus den (geladenen) Relationen – für Payloads mit Namen/Avatar.
     * @return Collection<int, User>
     */
    public function effectiveAdminUsers(): Collection
    {
        $this->loadMissing(['adminDepartments.users:id,first_name,last_name,profile_photo_path']);

        return collect($this->admins->all())
            ->merge($this->adminDepartments->flatMap(fn (Department $department) => $department->users->all()))
            ->unique('id')
            ->values();
    }

    /**
     * Alle Raumadmins inkl. Team-Mitgliedern als User (Benachrichtigungen).
     * @return Collection<int, User>
     */
    public function effectiveAdmins(): Collection
    {
        $ids = $this->effectiveAdminIds();

        return $ids === [] ? collect() : User::query()->whereIn('id', $ids)->get();
    }

    /**
     * @return HasMany<RoomFile, $this>
     */
    //@todo: fix phpcs error - refactor function name to roomFiles
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function room_files(): HasMany
    {
        return $this->hasMany(RoomFile::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return BelongsToMany<Room, $this>
     */
    //@todo: fix phpcs error - refactor function name to adjoiningRooms
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function adjoining_rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'adjoining_room_main_room', 'main_room_id', 'adjoining_room_id');
    }

    /**
     * @return BelongsToMany<Room, $this>
     */
    //@todo: fix phpcs error - refactor function name to mainRooms
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function main_rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'adjoining_room_main_room', 'adjoining_room_id', 'main_room_id');
    }

    /**
     * @return BelongsToMany<RoomCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(RoomCategory::class)->using(RoomRoomCategoryMapping::class);
    }

    /**
     * @return BelongsToMany<RoomAttribute, $this>
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(RoomAttribute::class)->using(RoomRoomAttributeMapping::class);
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed()
            ->orWhere('temporary', true)
            ->where('end_date', '<=', now()->subDays(14));
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
        ?Carbon $startDate,
        ?Carbon $endDate
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
        )->orderBy('position')->orderBy('id');
    }

    public function scopeNotIdIn(Builder $builder, array $ids): Builder
    {
        return $builder->whereNotIn('id', $ids);
    }
}
