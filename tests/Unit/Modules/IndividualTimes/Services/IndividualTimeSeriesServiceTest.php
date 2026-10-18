<?php

namespace Tests\Unit\Modules\IndividualTimes\Services;

use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Artwork\Modules\IndividualTimes\Services\IndividualTimeSeriesService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IndividualTimeSeriesServiceTest extends TestCase
{
    private IndividualTimeSeriesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IndividualTimeSeriesService::class);
    }

    #[Test]
    public function create_series_for_timeables_persists_series_and_individual_times(): void
    {
        $user = User::factory()->create();
        $data = [
            'title' => 'Weekly meeting',
            'start_date' => '2025-01-06', // Monday
            'end_date' => '2025-01-20',
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1], // Monday
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'break_minutes' => 0,
            'created_by' => $user->id,
        ];

        $series = $this->service->createSeriesForTimeables($data, new Collection([$user]));

        $this->assertInstanceOf(IndividualTimeSeries::class, $series);
        $this->assertTrue($series->exists);
        // 3 Mondays: 2025-01-06, 2025-01-13, 2025-01-20
        $this->assertSame(3, $user->individualTimes()->count());
    }

    #[Test]
    public function create_series_skips_timeables_without_relation(): void
    {
        $data = [
            'title' => 'Test',
            'start_date' => '2025-01-06',
            'end_date' => '2025-01-06',
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1],
            'full_day' => true,
            'created_by' => null,
        ];

        $bogus = new class {
            public int $id = 99;
        };

        $series = $this->service->createSeriesForTimeables($data, new Collection([$bogus]));

        $this->assertTrue($series->exists);
    }

    #[Test]
    public function an_overnight_series_entry_ends_on_the_next_day_with_correct_minutes(): void
    {
        // Vorher: Enddatum = Startdatum und working_time_minutes 0 (Carbon 3: negatives diffInMinutes)
        $user = User::factory()->create();
        $this->service->createSeriesForTimeables([
            'title' => 'Nachtprobe',
            'start_date' => '2025-01-06',
            'end_date' => '2025-01-06',
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1],
            'full_day' => false,
            'start_time' => '22:00',
            'end_time' => '04:00',
            'break_minutes' => 30,
            'created_by' => $user->id,
        ], new Collection([$user]));

        $entry = $user->individualTimes()->sole();
        $this->assertSame('2025-01-07', \Carbon\Carbon::parse($entry->end_date)->toDateString());
        $this->assertSame(330, (int) $entry->working_time_minutes);
    }

    #[Test]
    public function editing_a_single_time_moves_its_end_date_with_the_new_times(): void
    {
        // Vorher blieb nach 22–04 → 09–17 das alte Enddatum (Folgetag) stehen: über 24 h gezählt
        $user = User::factory()->create();
        $entry = $user->individualTimes()->create([
            'title' => 'Probe',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'start_time' => '22:00',
            'end_time' => '04:00',
            'full_day' => false,
            'working_time_minutes' => 360,
            'break_minutes' => 0,
        ]);
        $this->actingAs($admin = User::factory()->create());
        $admin->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

        $this->patch(route('individual-times.update-single', $entry), [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_minutes' => 30,
        ])->assertRedirect();

        $entry->refresh();
        $this->assertSame('2026-10-05', \Carbon\Carbon::parse($entry->end_date)->toDateString());
        $this->assertSame(450, (int) $entry->working_time_minutes);
    }
}
