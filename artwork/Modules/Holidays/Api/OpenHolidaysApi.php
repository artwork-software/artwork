<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;
use NoahNxT\LaravelOpenHolidaysApi\OpenHolidaysApi as VendorApi;

class OpenHolidaysApi implements HolidayApi
{
    public function __construct(
        private readonly VendorApi $vendorApi
    ) {
    }

    public function getHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        $response = $this->vendorApi->holidays()->publicHolidays(
            $subdivision->country_code,
            $languageCode,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $subdivision->country_code . '-' . $subdivision->code
        );

        if ($response->status() > 200) {
            foreach ($response->array()['errors'] as $field => $error) {
                //Might be multiple but we can only throw one exception at a time
                throw new \Exception(sprintf('Error in %s -> %s', $field, $error[0]));
            }
        }

        $return = [];

        foreach ($response->array() as $holidayData) {
            $return[] = new ApiDto(
                name: $holidayData['name'][0]['text'],
                date: Carbon::createFromFormat('Y-m-d', $holidayData['startDate']),
                subdivision: $subdivision,
                remoteIdentifier: $holidayData['id'],
            );
        }

        return $return;
    }
}
