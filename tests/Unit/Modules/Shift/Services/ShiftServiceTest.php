<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftService;
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
