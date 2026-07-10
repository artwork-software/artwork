<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;
use Exception;
use CalderoSystems\LaravelOpenHolidaysApi\OpenHolidaysApi as VendorApi;
use Saloon\Http\Response;

readonly class OpenHolidaysApi implements HolidayApi
{
    public function __construct(
        private VendorApi $vendorApi
    ) {
    }

    /**
     * @param Carbon $from
     * @param Carbon $to
     * @param Subdivision $subdivision
     * @param string|null $languageCode
     * @return array|ApiDto[]
     * @throws Exception
     */
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

        $this->ensureSuccessfulResponse($response);

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

    /**
     * @param Carbon $from
     * @param Carbon $to
     * @param Subdivision $subdivision
     * @param string|null $languageCode
     * @return array|ApiDto[]
     * @throws Exception
     */
    public function getSchoolHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        $response = $this->vendorApi->holidays()->schoolHolidays(
            $subdivision->country_code,
            $languageCode,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $subdivision->country_code . '-' . $subdivision->code
        );

        $this->ensureSuccessfulResponse($response);

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

    private function ensureSuccessfulResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $errors = $response->array()['errors'] ?? [];
        foreach ($errors as $field => $error) {
            $message = is_array($error) ? ($error[0] ?? 'Unknown error') : $error;

            throw new \RuntimeException(sprintf('Error in %s -> %s', $field, $message));
        }

        throw new \RuntimeException(sprintf(
            'Holiday API request failed with status %d',
            $response->status()
        ));
    }
}
