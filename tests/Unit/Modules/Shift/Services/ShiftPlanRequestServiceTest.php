<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Services\ShiftPlanRequestService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftPlanRequestServiceTest extends TestCase
{
    private ShiftPlanRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = app(ShiftPlanRequestService::class);
    }

    #[Test]
    public function create_request_with_shifts_persists_request(): void
    {
        $craft = Craft::factory()->create();
        $user = User::factory()->create();

        $request = $this->service->createRequestWithShifts([
            'craft_id' => $craft->id,
            'week_number' => 18,
            'year' => 2024,
            'status' => 'pending',
            'requested_by_user_id' => $user->id,
        ], [], false);

        $this->assertNotNull($request->id);
        $this->assertDatabaseHas('shift_plan_requests', [
            'id' => $request->id,
            'craft_id' => $craft->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function create_request_with_shifts_attaches_with_snapshot(): void
    {
        $craft = Craft::factory()->create();
        $user = User::factory()->create();
        $shift = Shift::factory()->create();

        $request = $this->service->createRequestWithShifts([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2024,
            'status' => 'pending',
            'requested_by_user_id' => $user->id,
        ], [$shift->id], true);

        $this->assertSame(1, $request->requestedShifts()->count());
        $attached = $request->requestedShifts->first();
        $this->assertNotEmpty($attached->pivot->snapshot);
        $snapshot = json_decode($attached->pivot->snapshot, true);
        $this->assertSame($shift->id, $snapshot['shift_id']);
    }

    #[Test]
    public function attach_shifts_to_request_does_not_duplicate(): void
    {
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id]);
        $shift = Shift::factory()->create();

        $this->service->attachShiftsToRequest($request, [$shift->id], false);
        $this->service->attachShiftsToRequest($request, [$shift->id], false);

        $this->assertSame(1, $request->requestedShifts()->count());
    }

    #[Test]
    public function attach_shifts_to_request_handles_empty_array(): void
    {
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id]);

        $this->service->attachShiftsToRequest($request, [], true);

        $this->assertSame(0, $request->requestedShifts()->count());
    }
}
