<?php

namespace Artwork\Core\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class HelperService
{
    public function getDateRangeByCalendarWeekAndYear(int $week, int $year): array
    {
        if ($week < 1 || $week > 53 || $year < 1970) {
            throw new \InvalidArgumentException('Invalid week or year provided.');
        }

        $carbon = Carbon::now()->setISODate($year, $week);

        $startDate = $carbon->copy()->startOfWeek(CarbonInterface::MONDAY);
        $endDate   = $carbon->copy()->endOfWeek(CarbonInterface::SUNDAY);

        // WICHTIG: numerisches Array!
        return [
            $startDate,
            $endDate,
        ];
    }
}
