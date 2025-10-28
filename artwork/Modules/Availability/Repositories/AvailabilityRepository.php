<?php

namespace Artwork\Modules\Availability\Repositories;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\Available;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityRepository
{
    public function getByIdAndModel(int $id, string $available): Collection
    {
        return Availability::where('available_id', $id)->where('available_type', $available)->get();
    }

    public function getVacationWithinInterval(Available $available, Carbon $from, Carbon $until): Collection
    {
        return $available->availabilities()
            ->where('start_time', '<=', $from)->where('end_time', '>=', $until)
            ->get();
    }

    public function delete(Collection|Availability $availability): void
    {
        if ($availability instanceof \Illuminate\Support\Collection) {
            $availability->each(function (Availability $item): void {
                // Delete related conflicts first, then the availability itself
                $item->conflicts()->each(function ($conflict): void {
                    $conflict->delete();
                });
                $item->delete();
            });
            return;
        }

        // Single model instance: delete its conflicts, then the model
        $availability->conflicts()->each(function ($conflict): void {
            $conflict->delete();
        });
        $availability->delete();
    }

    public function save(Availability $availability): Availability
    {
        $availability->save();
        return $availability;
    }
}
