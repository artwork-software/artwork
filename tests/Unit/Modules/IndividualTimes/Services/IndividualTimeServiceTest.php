<?php

namespace Tests\Unit\Modules\IndividualTimes\Services;

use Artwork\Modules\IndividualTimes\Services\IndividualTimeService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IndividualTimeServiceTest extends TestCase
{
    private IndividualTimeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IndividualTimeService::class);
    }

    #[Test]
    public function process_times_returns_correct_start_and_end_on_same_day(): void
    {
        $start = Carbon::parse('2025-01-01 09:00:00');

        [$startResult, $endResult] = $this->service->processTimes(
            $start,
            '09:00',
            '17:00',
            Carbon::parse('2025-01-01')
        );

        $this->assertSame('2025-01-01 09:00:00', $startResult->format('Y-m-d H:i:s'));
        $this->assertSame('2025-01-01 17:00:00', $endResult->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function process_times_advances_day_when_end_before_start(): void
    {
        $start = Carbon::parse('2025-01-01 23:00:00');

        [$startResult, $endResult] = $this->service->processTimes(
            $start,
            '23:00',
            '01:00',
            Carbon::parse('2025-01-01')
        );

        $this->assertSame('2025-01-01 23:00:00', $startResult->format('Y-m-d H:i:s'));
        $this->assertSame('2025-01-02 01:00:00', $endResult->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function create_for_model_throws_on_unsupported_model(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $bogus = new class {
            public int $id = 1;
        };

        $this->service->createForModel($bogus, 'Title', '09:00', '17:00', '2025-01-01', 0);
    }

    #[Test]
    public function create_for_model_creates_full_day_individual_time(): void
    {
        $user = User::factory()->create();

        $time = $this->service->createForModel($user, 'Reise', null, null, '2025-02-01');

        $this->assertTrue((bool) $time->full_day);
        $this->assertSame(1440, $time->working_time_minutes);
        $this->assertDatabaseHas('individual_times', [
            'id' => $time->id,
            'timeable_id' => $user->id,
            'title' => 'Reise',
        ]);
    }

}
