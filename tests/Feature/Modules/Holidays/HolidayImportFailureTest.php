<?php

namespace Tests\Feature\Modules\Holidays;

use App\Settings\HolidaySettings;
use Artwork\Modules\Holidays\Import\HolidayImport;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Services\HolidayService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * Fällt die Feiertags-Schnittstelle aus, bleiben die bisher importierten Feiertage erhalten.
 */
final class HolidayImportFailureTest extends FeatureTestCase
{
    #[Test]
    public function imported_holidays_survive_a_failing_api(): void
    {
        $subdivision = Subdivision::query()->firstOrCreate(
            ['code' => 'HH'],
            ['name' => 'Hamburg', 'country_code' => 'DE']
        );
        $settings = app(HolidaySettings::class);
        $settings->subdivisions = [$subdivision->id];
        $settings->public_holidays = true;
        $settings->school_holidays = false;
        $settings->save();

        $holiday = Holiday::query()->create([
            'name' => 'Neujahr',
            'date' => '2027-01-01',
            'end_date' => '2027-01-01',
            'country' => 'DE',
            'from_api' => true,
            'remote_identifier' => 'test-new-year',
        ]);

        Http::fake(['openholidaysapi.org/*' => Http::response('down', 503)]);

        $failed = false;
        try {
            (new HolidayImport())->handle(app(HolidayService::class));
        } catch (RuntimeException) {
            $failed = true;
        }

        $this->assertTrue($failed, 'The failing API should abort the import.');
        $this->assertNotNull(Holiday::query()->find($holiday->id));
    }
}
