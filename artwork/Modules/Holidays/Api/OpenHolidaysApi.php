<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use RuntimeException;

readonly class OpenHolidaysApi implements HolidayApi
{
    private const string BASE_URL = 'https://openholidaysapi.org';

    public function __construct(private Factory $http)
    {
    }

    /** @return ApiDto[] */
    public function getHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return $this->toDtos(
            $this->getRawHolidays($from, $to, $subdivision, $languageCode),
            $subdivision
        );
    }

    /** @return ApiDto[] */
    public function getSchoolHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return $this->toDtos(
            $this->getRawSchoolHolidays($from, $to, $subdivision, $languageCode),
            $subdivision
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getRawHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return $this->request('PublicHolidays', $from, $to, $subdivision, $languageCode);
    }

    /** @return array<int, array<string, mixed>> */
    public function getRawSchoolHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        return $this->request('SchoolHolidays', $from, $to, $subdivision, $languageCode);
    }

    /** @return array<int, array<string, mixed>> */
    private function request(
        string $endpoint,
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode,
    ): array {
        $response = $this->http
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 250, throw: false)
            ->get(self::BASE_URL . '/' . $endpoint, [
                'countryIsoCode' => $subdivision->country_code,
                'languageIsoCode' => $languageCode,
                'validFrom' => $from->format('Y-m-d'),
                'validTo' => $to->format('Y-m-d'),
                'subdivisionCode' => $subdivision->country_code . '-' . $subdivision->code,
            ]);

        $this->ensureSuccessfulResponse($response);

        return $response->json() ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $holidays
     * @return ApiDto[]
     */
    private function toDtos(array $holidays, Subdivision $subdivision): array
    {
        return array_map(
            static fn (array $holiday): ApiDto => new ApiDto(
                name: $holiday['name'][0]['text'],
                date: Carbon::createFromFormat('Y-m-d', $holiday['startDate']),
                subdivision: $subdivision,
                remoteIdentifier: $holiday['id'],
            ),
            $holidays
        );
    }

    private function ensureSuccessfulResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        foreach ($response->json('errors', []) as $field => $error) {
            $message = is_array($error) ? ($error[0] ?? 'Unknown error') : $error;

            throw new RuntimeException(sprintf('Error in %s -> %s', $field, $message));
        }

        throw new RuntimeException(sprintf(
            'Holiday API request failed with status %d',
            $response->status()
        ));
    }
}
