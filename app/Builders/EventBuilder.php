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
    public function occursAt(Carbon $dateTime, bool $respectiveToTime = true): self
    {
        if (! $respectiveToTime) {
            return $this->whereDate('start_time', '<=', $dateTime)
                ->whereDate('end_time', '>=', $dateTime);
        }

        return $this->where('start_time', '>=', $dateTime)
            ->where('end_time', '<=', $dateTime);
    }

    public function whereOccursBetween(CarbonInterface $start, CarbonInterface $end): self
    {
        return $this
            ->whereDate('start_time', '>=', $start)
            ->whereDate('end_time', '<=', $end);
    }

    public function visibleForUser(User $user): self
    {
        if ($user->can("admin rooms") || $user->hasRole('admin')) {
            return $this;
        }

        return $this
            ->whereHas('roomAdministrators', fn (Builder $builder) => $builder->where('id', $user->id));
    }
}
