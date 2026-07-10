<?php

namespace Tests\Unit\Modules\Holidays;

use Artwork\Modules\Holidays\Api\OpenHolidaysApi;
use Artwork\Modules\Holidays\Models\Subdivision;
use CalderoSystems\LaravelOpenHolidaysApi\OpenHolidaysApi as VendorApi;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

final class OpenHolidaysApiTest extends TestCase
{
    #[Test]
    public function an_empty_successful_response_is_not_treated_as_an_error(): void
    {
        $api = $this->apiWithResponse([], 204);

        $this->assertSame([], $api->getHolidays(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
            $this->subdivision()
        ));
    }

    #[Test]
    public function a_failed_response_without_validation_errors_has_a_stable_exception(): void
    {
        $api = $this->apiWithResponse([], 503);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Holiday API request failed with status 503');

        $api->getSchoolHolidays(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
            $this->subdivision()
        );
    }

    private function apiWithResponse(array $body, int $status): OpenHolidaysApi
    {
        $vendorApi = new VendorApi();
        $vendorApi->withMockClient(new MockClient([
            MockResponse::make($body, $status),
        ]));

        return new OpenHolidaysApi($vendorApi);
    }

    private function subdivision(): Subdivision
    {
        return new Subdivision([
            'country_code' => 'DE',
            'code' => 'HE',
        ]);
    }
}
