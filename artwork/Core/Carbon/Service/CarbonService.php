<?php

namespace Artwork\Core\Carbon\Service;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CarbonService
{
    public const GERMAN_DATE_FORMAT = 'd.m.Y';

    public const INTERNATIONAL_DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly Carbon $carbon,
        private readonly CarbonPeriod $carbonPeriod,
    ) {
    }

    public function create(string $date): Carbon
    {
        return $this->carbon->create($date);
    }

    public function getNow(): Carbon
    {
        return $this->carbon->clone()->now();
    }

    public function parseAndAddDay(string $date): Carbon
    {
        return $this->carbon->clone()->parse($date)->addDay();
    }

    public function parseAndAddThreeDays(string $date): Carbon
    {
        return $this->carbon->clone()->parse($date)->addDays(3);
    }

    public function parseAndAddWeek(string $date): Carbon
    {
        return $this->carbon->clone()->parse($date)->addWeek();
    }

    public function getTodayMidnight(): Carbon
    {
        return $this->carbon->clone()->now()->setTime(0, 0);
    }

    public function cloneAndAddWeek(Carbon $date): Carbon
    {
        return $date->clone()->addWeek();
    }

    public function createPeriodOf(Carbon $dateStart, Carbon $dateEnd): CarbonPeriod
    {
        return $this
            ->carbonPeriod
            ->clone()
            ->setStartDate($dateStart)
            ->setEndDate($dateEnd);
    }

    public function formatFromString(string $date, string $desiredFormat = 'd.m.Y'): string
    {
        return $this->carbon->clone()->parse($date)->format($desiredFormat);
    }

    public function getDesiredDateFormatFromLocale(string $locale): string
    {
        return match ($locale) {
            'de' => self::GERMAN_DATE_FORMAT,
            'en' => self::INTERNATIONAL_DATE_FORMAT,
        };
    }

    public function appendTimeToDateFormat(string $dateFormat, string $timeFormat = 'H:i:s'): string
    {
        return $dateFormat . ' ' . $timeFormat;
    }
}
