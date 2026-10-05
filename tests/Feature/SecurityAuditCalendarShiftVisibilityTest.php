<?php

namespace Tests\Feature;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Schicht-Karten im Kalender ("Schichten anzeigen", work_shifts) nur mit Dienstplan-Sichtrecht.
 */
final class SecurityAuditCalendarShiftVisibilityTest extends TestCase
{
    private const START = '2026-04-01';
    private const END = '2026-04-30';

    #[Test]
    public function calendar_payload_has_no_shifts_without_shift_plan_right_even_if_setting_is_on(): void
    {
        $room = $this->createRoomWithEventAndShift(isPlanning: false);
        $user = $this->actingAsUserWith([]);
        $user->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);

        $this->assertSame(0, $this->countShiftsInCalendarPayload($room, isPlanning: false));
    }

    #[Test]
    public function calendar_payload_contains_shifts_with_view_shift_plan_right(): void
    {
        $room = $this->createRoomWithEventAndShift(isPlanning: false);
        $user = $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN]);
        $user->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);

        $this->assertSame(1, $this->countShiftsInCalendarPayload($room, isPlanning: false));
    }

    #[Test]
    public function calendar_payload_contains_shifts_for_shift_planner_and_admin(): void
    {
        $room = $this->createRoomWithEventAndShift(isPlanning: false);

        $planner = $this->actingAsUserWith([PermissionEnum::SHIFT_PLANNER]);
        $planner->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);
        $this->assertSame(1, $this->countShiftsInCalendarPayload($room, isPlanning: false));

        $admin = $this->actingAsAdmin();
        $admin->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);
        // adminUser() hat die Relation bereits (als null) geladen.
        $admin->unsetRelation('calendar_settings');
        $this->assertSame(1, $this->countShiftsInCalendarPayload($room, isPlanning: false));
    }

    #[Test]
    public function planning_calendar_payload_has_no_shifts_without_shift_plan_right(): void
    {
        $room = $this->createRoomWithEventAndShift(isPlanning: true);

        $user = $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $user->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);
        $this->assertSame(0, $this->countShiftsInCalendarPayload($room, isPlanning: true));

        $viewer = $this->actingAsUserWith([
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
            PermissionEnum::VIEW_SHIFT_PLAN,
        ]);
        $viewer->calendar_settings()->updateOrCreate([], ['work_shifts' => true]);
        $this->assertSame(1, $this->countShiftsInCalendarPayload($room, isPlanning: true));
    }

    #[Test]
    public function saving_work_shifts_setting_without_right_is_forced_to_false(): void
    {
        $user = $this->actingAsUserWith([]);
        $settings = $user->calendar_settings()->updateOrCreate([], ['work_shifts' => false, 'project_status' => false]);

        $this->patch(route('user.calendar_settings.update', $user), [
            'work_shifts' => true,
            'project_status' => true,
        ])->assertSuccessful();

        $settings->refresh();
        $this->assertFalse((bool) $settings->work_shifts, 'Ohne Dienstplan-Sichtrecht bleibt work_shifts aus');
        $this->assertTrue((bool) $settings->project_status, 'Übrige Einstellungen werden weiterhin gespeichert');
    }

    #[Test]
    public function saving_work_shifts_setting_with_right_is_persisted(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN]);
        $settings = $user->calendar_settings()->updateOrCreate([], ['work_shifts' => false]);

        $this->patch(route('user.calendar_settings.update', $user), [
            'work_shifts' => true,
        ])->assertSuccessful();

        $this->assertTrue((bool) $settings->refresh()->work_shifts);
    }

    private function createRoomWithEventAndShift(bool $isPlanning): Room
    {
        $room = Room::factory()->create();

        // Termin im Raum, damit der Raum unabhängig von "Räume ohne Belegung ausblenden" geliefert wird
        Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => $isPlanning,
            'start_time' => '2026-04-10 10:00:00',
            'end_time' => '2026-04-10 12:00:00',
        ]);

        Shift::query()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        return $room;
    }

    private function countShiftsInCalendarPayload(Room $room, bool $isPlanning): int
    {
        $response = $this->getJson(route('events.all', [
            'start_date' => self::START,
            'end_date' => self::END,
            'isPlanning' => $isPlanning ? 'true' : 'false',
        ]))->assertOk();

        $count = 0;
        foreach ($response->json('calendar') as $roomData) {
            if ((int) $roomData['roomId'] !== $room->id) {
                continue;
            }
            foreach ($roomData['content'] as $day) {
                $count += count($day['shifts'] ?? []);
            }
        }

        return $count;
    }
}
