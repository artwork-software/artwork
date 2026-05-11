<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Services\UserShiftCalendarAboService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserShiftCalendarAboServiceTest extends TestCase
{
    private UserShiftCalendarAboService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserShiftCalendarAboService::class);
    }

    private function defaultPayload(): array
    {
        return [
            'date_range' => false,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
            'specific_crafts' => false,
            'craft_ids' => [],
            'enable_notification' => false,
            'notification_time' => 0,
            'notification_time_unit' => 'minutes',
        ];
    }

    #[Test]
    public function create_persists_shift_calendar_abo(): void
    {
        $user = User::factory()->create();

        $this->service->create($this->defaultPayload(), $user->id);

        $this->assertDatabaseHas('user_shift_calendar_abos', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function update_by_request_modifies_abo(): void
    {
        $user = User::factory()->create();
        $this->service->create($this->defaultPayload(), $user->id);
        $abo = UserShiftCalendarAbo::first();

        $this->service->updateByRequest($abo, ['notification_time' => 30, 'enable_notification' => true]);

        $fresh = $abo->fresh();
        $this->assertSame(30, $fresh->notification_time);
        $this->assertTrue((bool) $fresh->enable_notification);
    }

    #[Test]
    public function should_add_shift_returns_true_when_no_craft_restriction(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->specific_crafts = false;

        $shift = (object) ['craft_id' => 1];

        $this->assertTrue($this->service->shouldAddShift($abo, $shift));
    }

    #[Test]
    public function should_add_shift_filters_by_craft_ids(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->specific_crafts = true;
        $abo->craft_ids = [1, 2, 3];

        $this->assertTrue($this->service->shouldAddShift($abo, (object) ['craft_id' => 2]));
        $this->assertFalse($this->service->shouldAddShift($abo, (object) ['craft_id' => 99]));
    }

    #[Test]
    public function get_filtered_shifts_returns_sorted_collection(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->date_range = false;

        $shifts = new Collection([
            (object) ['start_date' => '2024-05-10', 'is_committed' => true],
            (object) ['start_date' => '2024-05-01', 'is_committed' => true],
        ]);

        $filtered = $this->service->getFilteredShifts($abo, $shifts);

        $this->assertSame('2024-05-01', $filtered->first()->start_date);
    }
}
