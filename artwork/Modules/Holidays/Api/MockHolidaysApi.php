<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;

class MockHolidaysApi implements HolidayApi
{
    /**
     * @param Carbon $from
     * @param Carbon $to
     * @param Subdivision $subdivision
     * @param string|null $languageCode
     * @return array|ApiDto[]
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
    public function getHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return [

        ];
    }


    /**
     * @param Carbon $from
     * @param Carbon $to
     * @param Subdivision $subdivision
     * @param string|null $languageCode
     * @return array|ApiDto[]
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
    public function getSchoolHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return [

        ];
    }
}
