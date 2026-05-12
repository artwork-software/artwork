<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftWorkerServiceTest extends TestCase
{
    private ShiftWorkerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftWorkerService::class);
    }

    #[Test]
    public function should_handle_series_shift_false_for_null_data(): void
    {
        $this->assertFalse($this->service->shouldHandleSeriesShift(null));
    }

    #[Test]
    public function should_handle_series_shift_false_when_only_this_day_true(): void
    {
        $this->assertFalse($this->service->shouldHandleSeriesShift(['onlyThisDay' => true]));
    }

    #[Test]
    public function should_handle_series_shift_true_when_only_this_day_false(): void
    {
        $this->assertTrue($this->service->shouldHandleSeriesShift(['onlyThisDay' => false]));
    }

    #[Test]
    public function is_same_shift_returns_true_for_same_id(): void
    {
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::query()->find($shift1->id);

        $this->assertTrue($this->service->isSameShift($shift1, $shift2));
    }

    #[Test]
    public function is_same_shift_returns_false_for_different_id(): void
    {
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();

        $this->assertFalse($this->service->isSameShift($shift1, $shift2));
    }

    #[Test]
    public function is_day_of_week_filtered_out_returns_false_for_all(): void
    {
        $shift = Shift::factory()->create();

        $this->assertFalse($this->service->isDayOfWeekFilteredOut('all', $shift));
    }

    #[Test]
    public function is_day_of_week_filtered_out_returns_true_for_mismatch(): void
    {
        $shift = Shift::factory()->create([
            'event_start_day' => '2024-05-06', // Monday
        ]);

        // Monday = 1, asking for Sunday (0) -> filtered out
        $this->assertTrue($this->service->isDayOfWeekFilteredOut('0', $shift));
    }
}
