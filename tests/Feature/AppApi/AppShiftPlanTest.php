<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppShiftPlanTest extends TestCase
{
    use CreatesUserShifts;

    #[Test]
    public function shiftPlanRequiresAuthentication(): void
    {
        $this->getJson(route('app.v1.shift-plan'))->assertUnauthorized();
    }

    #[Test]
    public function shiftPlanWithoutRosterPermissionReturns403(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.shift-plan'))->assertForbidden();
    }

    #[Test]
    public function shiftPlanDefaultsToCurrentWeek(): void
    {
        $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $this->getJson(route('app.v1.shift-plan'))
            ->assertOk()
            ->assertJsonPath('start', now()->startOfWeek()->toDateString())
            ->assertJsonPath('end', now()->endOfWeek()->toDateString())
            ->assertJsonCount(7, 'days');
    }

    #[Test]
    public function shiftPlanReturnsShiftWithEventProjectCraftAndColleagues(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $colleague = User::factory()->create();

        $date = now()->startOfWeek()->addDay();
        $shift = $this->createShiftForUser($user, $date);
        $this->assignToShift($shift, $colleague);

        $response = $this->getJson(route('app.v1.shift-plan'))->assertOk();
        $response->assertJsonStructure([
            'start',
            'end',
            'days' => [[
                'date',
                'total_work_time',
                'total_break_time',
                'shifts',
                'individual_times',
                'day_services',
                'holidays',
                'comments',
            ]],
        ]);

        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());
        $this->assertNotNull($day);
        $this->assertCount(1, $day['shifts']);

        $shiftPayload = $day['shifts'][0];
        $this->assertSame($shift->id, $shiftPayload['id']);
        $this->assertSame('18:00', $shiftPayload['start']);
        $this->assertSame('23:00', $shiftPayload['end']);
        $this->assertSame(30, $shiftPayload['break_minutes']);
        $this->assertTrue($shiftPayload['is_committed']);
        $this->assertSame($shift->event_id, $shiftPayload['event']['id']);
        $this->assertSame(
            Project::query()->find($shift->event->project_id)->name,
            $shiftPayload['project']['name'],
        );
        $this->assertSame(
            Craft::query()->find($shift->craft_id)->name,
            $shiftPayload['craft']['name'],
        );

        // The requesting user never appears in their own colleague list.
        $this->assertSame(
            [['id' => $colleague->id, 'type' => 'user', 'name' => $colleague->full_name]],
            $shiftPayload['colleagues'],
        );

        $this->assertNotSame('00:00', $day['total_work_time']);
    }

    #[Test]
    public function shiftPlanAcceptsACustomRange(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $date = now()->addWeeks(3)->startOfWeek();
        $this->createShiftForUser($user, $date);

        $response = $this->getJson(route('app.v1.shift-plan', [
            'start' => $date->toDateString(),
            'end' => $date->copy()->addDays(2)->toDateString(),
        ]))->assertOk()->assertJsonCount(3, 'days');

        $this->assertCount(1, $response->json('days.0.shifts'));
    }

    #[Test]
    public function shiftPlanRejectsInvalidRanges(): void
    {
        $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $this->getJson(route('app.v1.shift-plan', ['start' => '2026-07-20']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);

        $this->getJson(route('app.v1.shift-plan', ['start' => '2026-07-20', 'end' => '2026-07-19']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);

        $this->getJson(route('app.v1.shift-plan', ['start' => '2026-01-01', 'end' => '2026-12-31']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);
    }

    #[Test]
    public function shiftPlanAllows62DaysAndRejects63Days(): void
    {
        $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $start = now()->startOfDay();

        $this->getJson(route('app.v1.shift-plan', [
            'start' => $start->toDateString(),
            'end' => $start->copy()->addDays(61)->toDateString(),
        ]))->assertOk()->assertJsonCount(62, 'days');

        $this->getJson(route('app.v1.shift-plan', [
            'start' => $start->toDateString(),
            'end' => $start->copy()->addDays(62)->toDateString(),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['end']);
    }

    #[Test]
    public function shiftPlanUsesShiftDatesInsteadOfParentEventDates(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $date = now()->startOfWeek()->addDay();
        $shift = $this->createShiftForUser($user, $date);
        $shift->event->update([
            'start_time' => $date->copy()->subMonth(),
            'end_time' => $date->copy()->subMonth()->addHour(),
        ]);

        $response = $this->getJson(route('app.v1.shift-plan'))->assertOk();
        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());

        $this->assertSame($shift->id, $day['shifts'][0]['id']);
    }

    #[Test]
    public function shiftPlanNormalizesNullableEventNamesForTheAppContract(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $date = now()->startOfWeek()->addDay();
        $shift = $this->createShiftForUser($user, $date);
        $shift->event->update(['name' => null, 'eventName' => null]);

        $response = $this->getJson(route('app.v1.shift-plan'))->assertOk();
        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());

        $this->assertSame('', $day['shifts'][0]['event']['name']);
    }
}
