<?php

namespace Artwork\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Api\ApiDto;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Repository\HolidayRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class HolidayService
{
    public function __construct(
        private readonly HolidayRepository $holidayRepository
    ) {
    }

    public function create(
        string $name,
        Subdivision|array $subdivision,
        Carbon $date,
        string $countryCode,
        ?int $rota = 0,
        ?string $remote_identifier = null,
        ?bool $from_api = false
    ): Holiday {
        return $this->holidayRepository->create(
            name: $name,
            subdivision: $subdivision,
            date: $date,
            countryCode: $countryCode,
            rota: $rota,
            remote_identifier: $remote_identifier,
            from_api: $from_api
        );
    }

    public function createFromApi(ApiDto $apiDto): Holiday
    {
        if ($holiday = $this->holidayRepository->findBy('remote_identifier', $apiDto->remoteIdentifier)) {
            $holiday->subdivisions()->attach($apiDto->subdivision);
            return $holiday;
        }
        return $this->create(
            $apiDto->name,
            $apiDto->subdivision,
            $apiDto->date,
            $apiDto->subdivision->country_code,
            remote_identifier: $apiDto->remoteIdentifier,
            from_api: true
        );
    }

    public function getAllImported(): Collection
    {
        return $this->holidayRepository->findAllBy('from_api', true);
    }

    public function getAll(array $with = []): Collection
    {
        return $this->holidayRepository->findAll($with);
    }
    
    public function getBy(string $column, mixed $value): ?Holiday
    {
        return $this->holidayRepository->findBy($column, $value);
    }
}
