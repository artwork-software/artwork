<?php

namespace App\Builders;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Event
 * @extends Builder<TModelClass>
 */
class EventBuilder extends Builder
{
    public function occursAt(Carbon $dateTime, bool $respectiveToTime = false): self
    {
        if (! $respectiveToTime) {
            return $this->whereDate('start_time', '<=', $dateTime)
                ->whereDate('end_time', '>=', $dateTime);
        }

        return $this->where('start_time', '>=', $dateTime)
            ->where('end_time', '<=', $dateTime);
    }

    public function whereOccursBetween(CarbonInterface $start, CarbonInterface $end, bool $respectiveToTime = false): self
    {
        if (! $respectiveToTime) {
            return $this->where(fn (EventBuilder $builder) => $builder
                ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                    ->whereDate('start_time', '>=', $start)
                    ->whereDate('start_time', '<=', $end)
                )
                ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                    ->whereDate('end_time', '>=', $start)
                    ->whereDate('end_time', '<=', $end)
                )
            );
        }

        return $this->where(fn (EventBuilder $builder) => $builder
            ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                ->where('start_time', '>=', $start)
                ->where('start_time', '<=', $end)
            )
            ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                ->where('end_time', '>=', $start)
                ->where('end_time', '<=', $end)
            )
        );
    }

    public function visibleForUser(User $user): self
    {
        if ($user->can("admin rooms") || $user->hasRole('admin')) {
            return $this;
        }

        return $this
            ->whereHas('roomAdministrators', fn (Builder $builder) => $builder->where('id', $user->id));
    }

    public function withCollisionCount(): self
    {
        return $this->withCount([
            'sameRoomEvents as collision_count' => fn (EventBuilder $builder) => $builder
                ->where(fn (EventBuilder $sameTimeBuilder) => $sameTimeBuilder
                    ->where(fn (EventBuilder $startTimeInBetween) => $startTimeInBetween
                        ->whereColumn('start_time', '>=', 'events.start_time')
                        ->whereColumn('start_time', '<=', 'events.end_time')
                    )
                    ->orWhere(fn (EventBuilder $endTimeInBetween) => $endTimeInBetween
                        ->whereColumn('end_time', '>=', 'events.start_time')
                        ->whereColumn('end_time', '<=', 'events.end_time')
                    )
                )
                ->whereColumn('id', '!=', 'events.id')
        ]);
    }
}
