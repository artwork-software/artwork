<?php

namespace Artwork\Core\Carbon\Service;

use Carbon\Carbon;

class CarbonService
{
    public function __construct(private readonly Carbon $carbon)
    {
    }

    public function create(string $date): Carbon
    {
        return $this->carbon->create($date);
    }

    public function getNow(): Carbon
    {
        return $this->carbon->copy()->now();
    }

    public function parseAndAddDay(string $date): Carbon
    {
        return $this->carbon->copy()->parse($date)->addDay();
    }

    public function parseAndAddThreeDays(string $date): Carbon
    {
        return $this->carbon->copy()->parse($date)->addDays(3);
    }

    public function parseAndAddWeek(string $date): Carbon
    {
        return $this->carbon->copy()->parse($date)->addWeek();
    }

    public function getTodayMidnight(): Carbon
    {
        return $this->carbon->clone()->now()->setTime(0, 0);
    }
}
