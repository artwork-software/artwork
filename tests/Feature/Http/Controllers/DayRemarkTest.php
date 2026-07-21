<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Calendar\Events\DayRemarkUpdated;
use Artwork\Modules\Calendar\Models\DayRemark;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class DayRemarkTest extends FeatureTestCase
{
    private function enableDayRemarks(bool $mandatory = false): void
    {
        $settings = app(GeneralCalendarSettings::class);
        $settings->day_remarks_enabled = true;
        $settings->day_remarks_mandatory = $mandatory;
        $settings->save();
    }

    #[Test]
    public function guest_cannot_upsert_day_remarks(): void
    {
        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Test'])
            ->assertUnauthorized();
    }

    #[Test]
    public function user_without_edit_permission_cannot_upsert_day_remarks(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_VIEW]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Test'])
            ->assertForbidden();
    }

    #[Test]
    public function upsert_returns_not_found_when_feature_is_disabled(): void
    {
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Test'])
            ->assertNotFound();
    }

    #[Test]
    public function user_with_edit_permission_can_create_and_update_a_day_remark(): void
    {
        $this->enableDayRemarks();
        $user = $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Premierenwoche'])
            ->assertSuccessful()
            ->assertJsonPath('dayRemark.text', 'Premierenwoche');

        $this->assertDatabaseHas('day_remarks', [
            'date' => '2026-08-03',
            'remark' => 'Premierenwoche',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Geändert'])
            ->assertSuccessful()
            ->assertJsonPath('dayRemark.text', 'Geändert');

        $this->assertSame(1, DayRemark::query()->count());
        $this->assertDatabaseHas('day_remarks', ['date' => '2026-08-03', 'remark' => 'Geändert']);
    }

    #[Test]
    public function empty_remark_deletes_the_day_remark(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        DayRemark::query()->create(['date' => '2026-08-03', 'remark' => 'Weg damit']);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => '   '])
            ->assertSuccessful()
            ->assertJsonPath('dayRemark', null);

        $this->assertDatabaseMissing('day_remarks', ['date' => '2026-08-03']);
    }

    #[Test]
    public function remark_longer_than_limit_is_rejected(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => str_repeat('x', 501)])
            ->assertUnprocessable();
    }

    #[Test]
    public function invalid_date_is_rejected(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '03.08.2026']), ['remark' => 'Test'])
            ->assertUnprocessable();

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-02-30']), ['remark' => 'Test'])
            ->assertUnprocessable();
    }

    #[Test]
    public function calendar_period_contains_day_remark_for_user_with_view_permission(): void
    {
        $this->enableDayRemarks();
        $user = $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_VIEW]);

        DayRemark::query()->create(['date' => '2026-08-03', 'remark' => 'Sichtbar']);

        $period = app(CalendarDataService::class)->createCalendarPeriodDto(
            Carbon::parse('2026-08-03'),
            Carbon::parse('2026-08-04'),
            $user,
            false
        );

        $byDate = collect($period)->keyBy('date');
        $this->assertSame('Sichtbar', $byDate->get('2026-08-03')->dayRemark['text']);
        $this->assertNull($byDate->get('2026-08-04')->dayRemark);
    }

    #[Test]
    public function calendar_period_hides_day_remarks_without_view_permission(): void
    {
        $this->enableDayRemarks();
        $user = User::factory()->create();
        $this->actingAs($user);

        DayRemark::query()->create(['date' => '2026-08-03', 'remark' => 'Geheim']);

        $period = app(CalendarDataService::class)->createCalendarPeriodDto(
            Carbon::parse('2026-08-03'),
            Carbon::parse('2026-08-03'),
            $user,
            false
        );

        $this->assertNull(collect($period)->keyBy('date')->get('2026-08-03')->dayRemark);
    }

    #[Test]
    public function calendar_period_hides_day_remarks_when_feature_is_disabled(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_VIEW]);

        DayRemark::query()->create(['date' => '2026-08-03', 'remark' => 'Inaktiv']);

        $period = app(CalendarDataService::class)->createCalendarPeriodDto(
            Carbon::parse('2026-08-03'),
            Carbon::parse('2026-08-03'),
            $user,
            false
        );

        $this->assertNull(collect($period)->keyBy('date')->get('2026-08-03')->dayRemark);
    }

    #[Test]
    public function upsert_broadcasts_the_change_after_response(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Live'])
            ->assertSuccessful();

        // Der Broadcast läuft als Closure-Job nach dem Response (dispatch()->afterResponse())
        Bus::assertDispatchedAfterResponse(CallQueuedClosure::class);
    }

    #[Test]
    public function deleting_a_remark_broadcasts_after_response(): void
    {
        $this->enableDayRemarks();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        DayRemark::query()->create(['date' => '2026-08-03', 'remark' => 'Weg']);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => ''])
            ->assertSuccessful();

        Bus::assertDispatchedAfterResponse(CallQueuedClosure::class);
    }

    #[Test]
    public function day_remark_updated_event_broadcasts_on_the_day_remarks_channel(): void
    {
        $event = new DayRemarkUpdated('2026-08-03', ['text' => 'Live', 'updated_by' => 'Max', 'updated_at' => '03.08.2026 10:00']);

        $this->assertSame('day-remark.updated', $event->broadcastAs());
        $this->assertSame('private-day-remarks', $event->broadcastOn()->name);
        $this->assertSame(
            ['date' => '2026-08-03', 'dayRemark' => ['text' => 'Live', 'updated_by' => 'Max', 'updated_at' => '03.08.2026 10:00']],
            $event->broadcastWith()
        );
    }

    #[Test]
    public function user_can_toggle_show_day_remarks_display_setting(): void
    {
        $user = $this->actingAsUserWith([]);
        $user->calendar_settings()->create();

        $this->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
            'show_day_remarks' => false,
        ])->assertSuccessful();

        $this->assertFalse($user->calendar_settings()->first()->show_day_remarks);
    }
}
