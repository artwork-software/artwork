<?php

namespace Artwork\Modules\Holidays\Repository;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class HolidayRepository extends BaseRepository
{
    public function create(
        string $name,
        Subdivision|array $subdivision,
        Carbon $date,
        Carbon $endDate,
        string $countryCode,
        bool $yearly,
        ?int $rota = 0,
        ?string $remote_identifier = null,
        ?bool $from_api = false,
        ?string $color = null,
        ?bool $treatAsSpecialDay = false
    ): Holiday {
        $holiday = new Holiday();
        $holiday->fill([
            'name' => $name,
            'date' => $date,
            'end_date' => $endDate,
            'yearly' => $yearly,
            'rota' => $rota,
            'country' => $countryCode,
            'remote_identifier' => $remote_identifier,
            'from_api' => $from_api,
            'color' => $color,
            'treatAsSpecialDay' => $treatAsSpecialDay,
        ]);

        $holiday->save();
        if (!is_array($subdivision)) {
            $subdivision = [$subdivision];
        }
        $holiday->subdivisions()->attach($subdivision);

        return $holiday;
    }

    public function findBy(string $column, mixed $value): ?Holiday
    {
        return Holiday::where($column, $value)->first();
    }

    public function findAllBy(string $column, mixed $value): Collection
    {
        return Holiday::where($column, $value)->get();
    }

    public function findAll(int $paginate, array $with = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Holiday::query()->with($with)->orderBy('date', 'ASC')->paginate($paginate);
    }
}
