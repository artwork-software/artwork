<?php

namespace Artwork\Modules\Vacation\Repository;

use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class VacationRepository
{
    public function getByIdAndModel(int $id, string $vacationer): Collection
    {
        return Vacation::where('vacationer_id', $id)->where('vacationer_type', $vacationer)->get();
    }

    public function getVacationWithinInterval(Vacationer $vacationer, string $day): Collection
    {
        return $vacationer->vacations()->where('date', $day)->get();
    }

    // TODO: fix type matching
    public function delete(Collection|Vacation $vacations): void
    {
        if ($vacations->count() > 1) {
            $vacations->each(function ($vacation): void {
                $vacation->each(function ($vacationConflict): void {
                    $vacationConflict->delete();
                });
                $vacation->delete();
            });
            return;
        }
        $vacations->first()?->delete();
    }

    public function save(Vacation $vacation): Vacation
    {
        $vacation->save();
        return $vacation;
    }
}
