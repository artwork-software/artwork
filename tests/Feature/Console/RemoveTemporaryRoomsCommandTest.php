<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RemoveTemporaryRoomsCommandTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function a_temporary_room_stays_available_on_its_last_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));
        $lastDayToday = Room::factory()->create(['temporary' => true, 'end_date' => '2026-10-05 00:00:00']);
        $endedYesterday = Room::factory()->create(['temporary' => true, 'end_date' => '2026-10-04 00:00:00']);
        $permanent = Room::factory()->create(['temporary' => false, 'end_date' => '2026-10-01 00:00:00']);

        $this->artisan('artwork:remove-temporary-rooms')->assertSuccessful();

        $this->assertNotSoftDeleted($lastDayToday);
        $this->assertSoftDeleted($endedYesterday);
        $this->assertNotSoftDeleted($permanent);
    }
}
