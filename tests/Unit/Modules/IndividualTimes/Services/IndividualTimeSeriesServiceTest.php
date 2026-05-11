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
}
