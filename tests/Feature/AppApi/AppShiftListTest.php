<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppShiftListTest extends TestCase
{
    use CreatesUserShifts;

    #[Test]
    public function shiftListRequiresAuthentication(): void
    {
        $this->getJson(route('app.v1.shift-list'))->assertUnauthorized();
    }

    #[Test]
    public function shiftListWithoutShiftPlanPermissionReturns403(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.shift-list'))->assertForbidden();
    }

    #[Test]
    public function shiftListDefaultsToCurrentWeek(): void
    {
        $this->actingAsApiUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->getJson(route('app.v1.shift-list'))
            ->assertOk()
            ->assertJsonPath('start', now()->startOfWeek()->toDateString())
            ->assertJsonPath('end', now()->endOfWeek()->toDateString());
    }

    #[Test]
    public function shiftListGroupsShiftsByDayAndRoomWithStaffingCounts(): void
    {
        $planner = $this->actingAsApiUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $worker = User::factory()->create();

        $room = Room::factory()->create();
        $date = now()->startOfWeek()->addDay();
        $shift = $this->createShiftForUser($worker, $date, ['room_id' => $room->id]);
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'value' => 2,
        ]);

        $response = $this->getJson(route('app.v1.shift-list'))->assertOk();
        $response->assertJsonStructure([
            'start',
            'end',
            'days' => [['date', 'holidays', 'rooms' => [['id', 'name', 'shifts']]]],
        ]);

        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());
        $this->assertNotNull($day);
        $this->assertCount(1, $day['rooms']);
        $this->assertSame($room->id, $day['rooms'][0]['id']);

        $shiftPayload = $day['rooms'][0]['shifts'][0];
        $this->assertSame($shift->id, $shiftPayload['id']);
        $this->assertSame('18:00', $shiftPayload['start']);
        $this->assertSame('23:00', $shiftPayload['end']);
        $this->assertSame(2, $shiftPayload['required_count']);
        $this->assertSame(1, $shiftPayload['assigned_count']);
        $this->assertCount(1, $shiftPayload['workers']);
        $this->assertSame('user', $shiftPayload['workers'][0]['type']);
        $this->assertSame($worker->full_name, $shiftPayload['workers'][0]['name']);
        $this->assertFalse($shiftPayload['workers'][0]['is_unavailable']);
        // The planner themself is not assigned — the list shows all workers, unlike
        // the personal shift plan which filters the requesting user out.
        $this->assertNotSame($planner->id, $shiftPayload['workers'][0]['id']);
    }
}
