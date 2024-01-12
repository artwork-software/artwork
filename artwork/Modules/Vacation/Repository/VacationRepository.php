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

    public function getVacationWithinInterval(Vacationer $vacationer, Carbon $from, Carbon $until): Collection
    {
        return $vacationer->vacations()
            ->where('from', '<=', $from)->where('until', '>=', $until)
            ->get();
    }

    public function delete(Collection|Vacation $vacations): void
    {
        $vacations->delete();
    }

    public function save(Vacation $vacation): Vacation
    {
        $vacation->save();
        return $vacation;
    }
}
