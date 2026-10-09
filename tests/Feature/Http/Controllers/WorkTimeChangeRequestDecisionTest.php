<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

final class WorkTimeChangeRequestDecisionTest extends FeatureTestCase
{
    private function givePermission(User $user, PermissionEnum $permission): void
    {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        $user->givePermissionTo($permission->value);
    }

    private function createRequest(Craft $craft, string $status = 'pending'): WorkTimeChangeRequest
    {
        $worker = User::factory()->create();

        return WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'craft_id' => $craft->id,
            'status' => $status,
            'requested_by' => $worker->id,
        ]);
    }

    #[Test]
    public function craft_shift_planner_can_decline_a_pending_request(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);
        $craft->craftShiftPlaner()->attach($planner->id);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request), [
                'decline_message' => 'Passt leider nicht.',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame($planner->id, $request->declined_by);
        $this->assertSame('Passt leider nicht.', $request->decline_comment);
    }

    #[Test]
    public function planner_may_decide_for_crafts_assignable_by_all(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request), [
                'decline_message' => 'Nein.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
    }

    #[Test]
    public function user_without_shift_planner_permission_gets_403(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $user = User::factory()->create();

        $request = $this->createRequest($craft);

        $this->actingAs($user)
            ->post(route('worktime.change-request.decline', $request))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('worktime.change-request.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    #[Test]
    public function planner_of_another_craft_gets_403(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request))
            ->assertForbidden();

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    #[Test]
    public function already_decided_requests_cannot_be_decided_again(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $approved = $this->createRequest($craft, 'approved');
        $rejected = $this->createRequest($craft, 'rejected');

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $approved))
            ->assertForbidden();

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $rejected))
            ->assertForbidden();
    }

    #[Test]
    public function approving_a_request_sets_the_individual_time_like_the_shift_plan(): void
    {
        // Regression: Die Genehmigung schrieb nur die Uhrzeiten — ein altes +1-Tag-end_date (frühere
        // Über-Mitternacht-Zeit) blieb stehen (28h-Zuweisung), Stunden-Cache und Ansichten blieben alt.
        Event::fake([UpdateShiftInShiftPlan::class]);
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);
        $worker = User::factory()->create();
        $shiftDate = now()->addDays(3)->toDateString();
        $nextDay = now()->addDays(4)->toDateString();

        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
            'start' => '10:00:00',
            'end' => '18:00:00',
        ]);
        $qualification = ShiftQualification::factory()->create();
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => $qualification->id,
            'craft_abbreviation' => 'X',
            'start_date' => $shiftDate,
            'end_date' => $nextDay,
            'start_time' => '22:00',
            'end_time' => '02:00',
        ]);

        $request = WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'craft_id' => $craft->id,
            'status' => 'pending',
            'requested_by' => $worker->id,
        ]);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $worker->id,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
        ]);
        $this->assertSame('approved', $request->fresh()->status);
        Event::assertDispatched(UpdateShiftInShiftPlan::class);
    }

    #[Test]
    public function approval_rebooks_the_booked_shift_day_instead_of_the_approval_day(): void
    {
        // Vorher: Korrekturzeile am Genehmigungstag mit worked_hours 0 -> Delta im falschen Tag/Monat,
        // in keiner Ist-Anzeige sichtbar. Jetzt: der bereits gebuchte Schichttag wird neu gebucht.
        Event::fake([UpdateShiftInShiftPlan::class]);
        [$craft, $planner] = $this->plannerForAllCrafts();
        $worker = $this->workShiftUserWithTuesdayTarget('08:00');
        $request = $this->requestForPastShift($craft, $worker, '2026-07-14', '10:00', '20:00'); // Dienstag

        Carbon::setTestNow(Carbon::parse('2026-07-14 23:59:00'));
        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();
        $this->assertSame(0, (int) $worker->fresh()->work_time_balance); // 8 h gearbeitet, Soll 8 h

        Carbon::setTestNow(Carbon::parse('2026-07-21 14:00:00'));
        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertRedirect();

        $this->assertSame(120, (int) $worker->fresh()->work_time_balance);
        $this->assertBookings($worker, ['daily_work_time_booking_2026-07-14' => 120]);

        // Nachtlauf am Genehmigungstag bucht nur den eigenen Tag (keine Arbeit, Soll 8 h)
        Carbon::setTestNow(Carbon::parse('2026-07-21 23:59:00'));
        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();
        app(WorkTimeBookingService::class)->calculateDailyWorkingHours(); // idempotent

        $this->assertSame(120 - 480, (int) $worker->fresh()->work_time_balance);
        $this->assertBookings($worker, [
            'daily_work_time_booking_2026-07-14' => 120,
            'daily_work_time_booking_2026-07-21' => -480,
        ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function approval_for_a_never_booked_day_does_not_touch_the_balance(): void
    {
        // Entscheidung: nie gebuchte Tage nur per „Tag neu buchen“ – die Genehmigung ändert dort nur die Zeit
        Event::fake([UpdateShiftInShiftPlan::class]);
        Carbon::setTestNow(Carbon::parse('2026-07-21 14:00:00'));
        [$craft, $planner] = $this->plannerForAllCrafts();
        $worker = $this->workShiftUserWithTuesdayTarget('08:00');
        $request = $this->requestForPastShift($craft, $worker, '2026-07-17', '10:00', '19:00');

        $this->actingAs($planner)->post(route('worktime.change-request.approve', $request))->assertRedirect();

        $this->assertSame(0, (int) $worker->fresh()->work_time_balance);
        $this->assertSame(0, $worker->workTimeBookings()->count());
        $this->assertDatabaseHas('shift_workers', ['employable_id' => $worker->id, 'end_time' => '19:00:00']);

        Carbon::setTestNow();
    }

    #[Test]
    public function extending_a_night_shift_is_not_booked_twice(): void
    {
        // Vorher: Schicht Mo 22:00 – Di 02:00, am Dienstag auf 04:00 verlängert -> Korrektur +120 UND der
        // Nachtlauf am Dienstag zählte 00–04 Uhr erneut. Jetzt: Montag bleibt, Dienstag bucht der Nachtlauf.
        Event::fake([UpdateShiftInShiftPlan::class]);
        [$craft, $planner] = $this->plannerForAllCrafts();
        $worker = $this->workShiftUserWithTuesdayTarget('08:00'); // Montag Soll 0
        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'start' => '22:00:00',
            'end' => '02:00:00',
        ]);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'craft_abbreviation' => 'X',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'start_time' => '22:00',
            'end_time' => '02:00',
        ]);
        $request = WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'request_start_time' => '22:00',
            'request_end_time' => '04:00',
            'request_end_date' => '2026-07-21',
            'craft_id' => $craft->id,
            'status' => 'pending',
            'requested_by' => $worker->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-20 23:59:00'));
        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();
        $this->assertSame(120, (int) $worker->fresh()->work_time_balance); // 22–24 Uhr, Soll 0

        Carbon::setTestNow(Carbon::parse('2026-07-21 10:00:00'));
        $this->actingAs($planner)->post(route('worktime.change-request.approve', $request))->assertRedirect();
        $this->assertSame(120, (int) $worker->fresh()->work_time_balance); // Montag unverändert

        Carbon::setTestNow(Carbon::parse('2026-07-21 23:59:00'));
        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();

        // Montag 2 h + Dienstag 4 h gegen 8 h Soll = 120 + (240 − 480)
        $this->assertSame(-120, (int) $worker->fresh()->work_time_balance);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: Craft, 1: User}
     */
    private function plannerForAllCrafts(): array
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        return [$craft, $planner];
    }

    private function workShiftUserWithTuesdayTarget(string $tuesdayTime): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'work_time_balance' => 0]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'tuesday' => $tuesdayTime,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    /**
     * Schicht 10–18 Uhr am angegebenen (vergangenen) Tag plus offene Änderungsanfrage.
     */
    private function requestForPastShift(
        Craft $craft,
        User $worker,
        string $date,
        string $requestStart,
        string $requestEnd
    ): WorkTimeChangeRequest {
        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '10:00:00',
            'end' => '18:00:00',
        ]);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'craft_abbreviation' => 'X',
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        return WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'request_start_time' => $requestStart,
            'request_end_time' => $requestEnd,
            'craft_id' => $craft->id,
            'status' => 'pending',
            'requested_by' => $worker->id,
        ]);
    }

    /**
     * @param array<string, int> $expected name => work_time_balance_change
     */
    private function assertBookings(User $worker, array $expected): void
    {
        $actual = $worker->workTimeBookings()
            ->pluck('work_time_balance_change', 'name')
            ->map(fn ($change): int => (int) $change)
            ->sortKeys()
            ->all();
        ksort($expected);

        $this->assertSame($expected, $actual);
        $this->assertSame(count($expected), $worker->workTimeBookings()->count());
    }
}
