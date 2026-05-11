<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Artwork\Modules\Shift\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftServiceTest extends TestCase
{
    private ShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();
        EventFacade::fake();
        $this->service = app(ShiftService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        Shift::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function get_by_id_returns_shift(): void
    {
        $shift = Shift::factory()->create();

        $found = $this->service->getById($shift->id);

        $this->assertNotNull($found);
        $this->assertSame($shift->id, $found->id);
    }

    #[Test]
    public function get_by_id_returns_null_when_missing(): void
    {
        $found = $this->service->getById(99999);

        $this->assertNull($found);
    }

    #[Test]
    public function create_shift_by_series_event_persists_record(): void
    {
        $event = Event::factory()->create();
        $craft = Craft::factory()->create();

        $shift = $this->service->createShiftBySeriesEvent(
            $event,
            [
                'start' => Carbon::parse('2024-05-06 08:00')->format('Y-m-d H:i'),
                'end' => Carbon::parse('2024-05-06 16:00')->format('Y-m-d H:i'),
                'break_minutes' => 30,
                'description' => 'Series shift',
            ],
            $craft->id
        );

        $this->assertNotNull($shift->id);
        $this->assertSame('Series shift', $shift->description);
        $this->assertSame($event->id, $shift->event_id);
        $this->assertSame($craft->id, $shift->craft_id);
    }

    #[Test]
    public function create_from_shift_preset_shift_for_event_creates_shift(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2024-05-06 08:00:00',
            'end_time' => '2024-05-06 16:00:00',
        ]);
        $craft = Craft::factory()->create();
        $shiftPreset = ShiftPreset::factory()->create();
        $presetShift = PresetShift::factory()->create([
            'shift_preset_id' => $shiftPreset->id,
            'start' => '09:00',
            'end' => '17:00',
            'break_minutes' => 45,
            'craft_id' => $craft->id,
            'description' => 'Preset desc',
        ]);

        $shift = $this->service->createFromShiftPresetShiftForEvent($presetShift, $event);

        $this->assertNotNull($shift->id);
        $this->assertSame($event->id, $shift->event_id);
        $this->assertSame($craft->id, $shift->craft_id);
        $this->assertSame(45, (int) $shift->break_minutes);
        $this->assertSame('Preset desc', $shift->description);
        $this->assertFalse((bool) $shift->is_committed);
    }

    #[Test]
    public function force_delete_removes_shift(): void
    {
        $shift = Shift::factory()->create();

        $this->service->forceDelete($shift);

        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function force_delete_shifts_removes_collection(): void
    {
        $shifts = Shift::factory()->count(3)->create();

        $this->service->forceDeleteShifts($shifts);

        foreach ($shifts as $shift) {
            $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
        }
    }
}
