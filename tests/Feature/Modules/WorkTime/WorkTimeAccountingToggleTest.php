<?php

namespace Tests\Feature\Modules\WorkTime;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\WorkingHourService;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

/**
 * Globaler Schalter „Arbeitszeitberechnung“ in den Schichteinstellungen
 * (shift-settings.work_time_accounting_enabled).
 */
final class WorkTimeAccountingToggleTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function setEnabled(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->work_time_accounting_enabled = $enabled;
        $settings->save();
    }

    private function workShiftUserWithTuesdayTarget(): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'work_time_balance' => 90]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'tuesday' => '08:00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    #[Test]
    public function setting_is_enabled_by_default_and_shared_with_frontend(): void
    {
        $this->actingAsAdmin();

        $this->assertTrue(WorkTimeAccounting::isEnabled());

        $this->get(route('shift.settings'))
            ->assertInertia(fn ($page) => $page->where('work_time_accounting_enabled', true));
    }

    #[Test]
    public function admin_can_toggle_setting(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('shift.settings.update.work-time-accounting-enabled'), [
            'work_time_accounting_enabled' => false,
        ])->assertRedirect();

        $this->assertFalse(app(ShiftSettings::class)->refresh()->work_time_accounting_enabled);

        $this->get(route('shift.settings'))
            ->assertInertia(fn ($page) => $page->where('work_time_accounting_enabled', false));
    }

    #[Test]
    public function user_without_shift_settings_access_cannot_toggle_setting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('shift.settings.update.work-time-accounting-enabled'), [
            'work_time_accounting_enabled' => false,
        ])->assertForbidden();

        $this->assertTrue(app(ShiftSettings::class)->refresh()->work_time_accounting_enabled);
    }

    #[Test]
    public function nightly_booking_books_nothing_when_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag
        $user = $this->workShiftUserWithTuesdayTarget();
        $this->setEnabled(false);

        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();

        $this->assertDatabaseMissing('work_time_bookings', ['user_id' => $user->id]);
        $this->assertSame(90, (int) $user->refresh()->work_time_balance);
    }

    #[Test]
    public function nightly_booking_resumes_after_reactivation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag
        $user = $this->workShiftUserWithTuesdayTarget();
        $this->setEnabled(false);
        $this->setEnabled(true);

        app(WorkTimeBookingService::class)->calculateDailyWorkingHours();

        $this->assertDatabaseHas('work_time_bookings', [
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_2026-07-21',
        ]);
    }

    #[Test]
    public function work_time_endpoints_are_closed_when_disabled(): void
    {
        $this->setEnabled(false);
        $this->actingAsAdmin();
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->post(route('users.worktimes.store', $worker), [
            'date' => '2026-07-21',
            'hours' => '01:00',
        ])->assertForbidden();
        $this->postJson(route('user.overtime.payout', $worker), ['minutes' => 60])->assertForbidden();
        $this->getJson(route('shift.user-info.overtime', $worker))->assertForbidden();
        $this->get(route('user.edit.overtime', $worker))->assertForbidden();
        $this->get(route('user.edit.worktimes', $worker))->assertForbidden();
        $this->get(route('shift.work-time-pattern'))->assertForbidden();

        $this->assertDatabaseMissing('work_time_bookings', ['user_id' => $worker->id]);
    }

    #[Test]
    public function work_time_pages_stay_open_when_enabled(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $this->get(route('user.edit.overtime', $worker))->assertOk();
        $this->get(route('shift.work-time-pattern'))->assertOk();
    }

    #[Test]
    public function balance_is_not_delivered_when_disabled(): void
    {
        $user = $this->workShiftUserWithTuesdayTarget();
        $service = app(WorkingHourService::class);

        $this->assertSame(90, $service->workTimeBalanceData($user, true)['workTimeBalanceMinutes']);

        $this->setEnabled(false);

        $this->assertSame([
            'workTimeBalance' => null,
            'workTimeBalanceFormatted' => null,
            'workTimeBalanceMinutes' => null,
        ], $service->workTimeBalanceData($user, true));
    }

    #[Test]
    public function weekly_hours_keep_planned_hours_but_drop_targets_when_disabled(): void
    {
        $user = $this->workShiftUserWithTuesdayTarget();
        $service = app(WorkingHourService::class);
        $start = Carbon::parse('2026-07-20');
        $end = Carbon::parse('2026-07-26');

        $enabledWeek = $service->calculateWeeklyWorkingHours($user, $start, $end)['30'];
        $this->assertSame(480, $enabledWeek['target_minutes']);

        $this->setEnabled(false);
        $disabledWeek = $service->calculateWeeklyWorkingHours($user, $start, $end)['30'];

        $this->assertSame($enabledWeek['planned_minutes'], $disabledWeek['planned_minutes']);
        $this->assertNotNull($disabledWeek['planned_formatted']);
        $this->assertNull($disabledWeek['target_minutes']);
        $this->assertNull($disabledWeek['daily_target_formatted']);
        $this->assertNull($disabledWeek['difference_formatted']);
        $this->assertFalse($disabledWeek['target_unknown']);
        $this->assertFalse($disabledWeek['isMinus']);
    }

    #[Test]
    public function users_list_does_not_flag_missing_work_time_patterns_when_disabled(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $flagFor = function () use ($worker): ?bool {
            $users = $this->get(route('users'))->viewData('page')['props']['users'];
            $users = $users['data'] ?? $users;

            foreach ($users as $listedUser) {
                if ((int) $listedUser['id'] === $worker->id) {
                    return (bool) $listedUser['work_time_pattern_missing'];
                }
            }

            return null;
        };

        $this->assertTrue($flagFor());

        $this->setEnabled(false);

        $this->assertFalse($flagFor());
    }

    #[Test]
    public function approving_a_change_request_for_a_past_shift_does_not_book_when_disabled(): void
    {
        Event::fake([UpdateShiftInShiftPlan::class]);
        $this->setEnabled(false);
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        Permission::query()->firstOrCreate(['name' => PermissionEnum::SHIFT_PLANNER->value, 'guard_name' => 'web']);
        $planner->givePermissionTo(PermissionEnum::SHIFT_PLANNER->value);
        $worker = User::factory()->create(['work_time_balance' => 30]);
        $shiftDate = now()->subDays(3)->toDateString();

        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
            'start' => '10:00:00',
            'end' => '18:00:00',
        ]);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'craft_abbreviation' => 'X',
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);
        $request = WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'request_start_time' => '08:00',
            'request_end_time' => '18:00',
            'craft_id' => $craft->id,
            'status' => 'pending',
            'requested_by' => $worker->id,
        ]);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $worker->id,
            'start_time' => '08:00:00',
        ]);
        $this->assertDatabaseMissing('work_time_bookings', ['user_id' => $worker->id]);
        $this->assertSame(30, (int) $worker->refresh()->work_time_balance);
    }
}
