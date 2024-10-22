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
        string $countryCode,
        ?int $rota = 0,
        ?string $remote_identifier = null,
        ?bool $from_api = false
    ): Holiday
    {
        $holiday = new Holiday();
        $holiday->fill([
            'name' => $name,
            'date' => $date,
            'rota' => $rota,
            'country' => $countryCode,
            'remote_identifier' => $remote_identifier,
            'from_api' => $from_api,
        ]);

        $holiday->save();

        if(!is_array($subdivision)) {
            $subdivision = [$subdivision];
        }

        foreach($subdivision as $sub) {
            $holiday->subdivisions()->attach($sub->id);
        }

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

    public function findAll(array $with = []): Collection
    {
        return Holiday::query()->with($with)->get();
    }
}
