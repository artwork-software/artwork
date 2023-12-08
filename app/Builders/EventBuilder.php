<?php

namespace App\Builders;

use App\Enums\RoleNameEnum;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class EventBuilder extends Builder
{
    /**
     * @param Carbon $dateTime
     * @param bool $respectiveToTime
     * @return self
     */
    public function occursAt(Carbon $dateTime, bool $respectiveToTime = false): self
    {
        if (! $respectiveToTime) {
            return $this->whereDate('start_time', '<=', $dateTime)
                ->whereDate('end_time', '>=', $dateTime);
        }

        return $this->where('start_time', '>=', $dateTime)
            ->where('end_time', '<=', $dateTime);
    }

    /**
     * @param CarbonInterface $start
     * @param CarbonInterface $end
     * @param bool $respectiveToTime
     * @return self
     */
    public function whereOccursBetween(
        CarbonInterface $start,
        CarbonInterface $end,
        bool $respectiveToTime = false
    ): self {
        if (! $respectiveToTime) {
            return $this->where(fn (EventBuilder $builder) => $builder
                ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                    ->whereDate('start_time', '>=', $start)
                    ->whereDate('start_time', '<=', $end))
                ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                    ->whereDate('end_time', '>=', $start)
                    ->whereDate('end_time', '<=', $end)));
        }

        return $this->where(fn (EventBuilder $builder) => $builder
            ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                ->where('start_time', '>=', $start)
                ->where('start_time', '<=', $end))
            ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                ->where('end_time', '>=', $start)
                ->where('end_time', '<=', $end)));
    }

    /**
     * @param User $user
     * @return self
     */
    public function visibleForUser(User $user): self
    {
        if ($user->can("admin rooms") || $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            return $this;
        }

        return $this
            ->whereHas('roomAdministrators', fn (Builder $builder) => $builder->where('id', $user->id));
    }

    /**
     * @return self
     */
    public function withCollisionCount(): self
    {
        return $this->withCount([
            'sameRoomEvents as collision_count' => fn (EventBuilder $builder) => $builder
                ->where(fn (EventBuilder $sameTimeBuilder) => $sameTimeBuilder->whereHasCollision())
                ->whereColumn('id', '!=', 'events.id')
        ]);
    }

    /**
     * @return self
     */
    public function whereHasCollision(): self
    {
        return $this->where(fn (EventBuilder $sameTimeBuilder) => $sameTimeBuilder
            ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                ->whereColumn('start_time', '>=', 'events.start_time')
                ->whereColumn('start_time', '<=', 'events.end_time'))
            ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                ->whereColumn('end_time', '>=', 'events.start_time')
                ->whereColumn('end_time', '<=', 'events.end_time')));
    }

    /**
     * @param array $filter
     * @return self
     */
    public function applyFilter(array $filter): self
    {
        if (!(empty($filter['roomIds']) && empty($filter['areaIds']) && empty($filter['roomAttributeIds']))) {
            $this->whereHas(
                'room',
                fn (Builder $roomBuilder) => $roomBuilder->when(
                    !empty($filter['roomIds']),
                    fn (Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $filter['roomIds'])
                )->when(
                    !empty($filter['areaIds']),
                    fn (Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $filter['areaIds'])
                )->when(
                    !empty($filter['roomAttributeIds']),
                    fn (Builder $roomBuilder) => $roomBuilder
                    ->whereHas('attributes', fn (Builder $roomAttributeBuilder) => $roomAttributeBuilder
                        ->whereIn('room_attributes.id', $filter['roomAttributeIds']))
                )
            );
        }

        if (!empty($filter['eventTypeIds'])) {
            $this->whereIn('event_type_id', $filter['eventTypeIds']);
        }

        if (!is_null($filter['isLoud'])) {
            $this->where('is_loud', $filter['isLoud']);
        }

        if (!is_null($filter['hasAudience'])) {
            $this->where('audience', $filter['hasAudience']);
        }

        if (!is_null($filter['adjoiningNoAudience'])) {
            $this->whereHas('adjoiningEvents', fn (EventBuilder $eventBuilder) => $eventBuilder
                ->whereHasCollision()
                ->where('audience', false))->orWhereDoesntHave('adjoiningEvents');
        }

        if (!is_null($filter['adjoiningNotLoud'])) {
            $this->whereHas('adjoiningEvents', fn (EventBuilder $eventBuilder) => $eventBuilder
                ->whereHasCollision()
                ->where('is_loud', false))->orWhereDoesntHave('adjoiningEvents');
        }

        return $this;
    }
}
