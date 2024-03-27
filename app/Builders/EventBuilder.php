<?php

namespace App\Builders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
}
