<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserShiftCalendarAboControllerTest extends FeatureTestCase
{
    #[Test]
    public function shiftCalendarFeedContainsIndividualTimesWithinTheSubscriptionRange(): void
    {
        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user, [
            'date_range' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $includedIndividualTime = $this->createIndividualTime($user, [
            'title' => 'Vorbereitung',
            'start_time' => '09:00',
            'end_time' => '11:30',
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'full_day' => false,
        ]);

        $this->createIndividualTime($user, [
            'title' => 'Nicht im Zeitraum',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'full_day' => false,
        ]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('SUMMARY:Individuelle Zeit: Vorbereitung', false)
            ->assertSee('UID:individual-time-' . $includedIndividualTime->id, false)
            ->assertDontSee('Nicht im Zeitraum', false);

        $this->assertMatchesRegularExpression(
            '/DTSTART[^:]*:20260715T090000/',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/DTEND[^:]*:20260715T113000/',
            $response->getContent()
        );
    }

    #[Test]
    public function shiftCalendarFeedExportsFullDayIndividualTimesAsAllDayEvents(): void
    {
        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user);

        $this->createIndividualTime($user, [
            'title' => 'Fortbildung',
            'start_time' => null,
            'end_time' => null,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'full_day' => true,
        ]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));

        $response->assertOk()
            ->assertSee('SUMMARY:Individuelle Zeit: Fortbildung', false);

        $this->assertMatchesRegularExpression(
            '/DTSTART[^:]*;VALUE=DATE:20260720/',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/DTEND[^:]*;VALUE=DATE:20260722/',
            $response->getContent()
        );
    }

    #[Test]
    public function shiftCalendarFeedExportsDayServicesAsAllDayEvents(): void
    {
        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user, [
            'date_range' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $dayService = DayService::factory()->create(['name' => 'Abendschluss']);
        $user->dayServices()->attach($dayService->id, ['date' => '2026-07-15']);
        $dayServiceAssignment = $user->dayServices()
            ->withPivot('id')
            ->wherePivot('date', '2026-07-15')
            ->firstOrFail();

        $outOfRangeDayService = DayService::factory()->create(['name' => 'Nicht im Zeitraum']);
        $user->dayServices()->attach($outOfRangeDayService->id, ['date' => '2026-08-05']);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));

        $response->assertOk()
            ->assertSee('SUMMARY:Tagesdienst: Abendschluss', false)
            ->assertSee('UID:day-service-' . $dayServiceAssignment->pivot->id, false)
            ->assertDontSee('Nicht im Zeitraum', false);

        $this->assertMatchesRegularExpression(
            '/DTSTART[^:]*;VALUE=DATE:20260715/',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/DTEND[^:]*;VALUE=DATE:20260716/',
            $response->getContent()
        );
    }

    #[Test]
    public function shiftCalendarFeedKeepsDayServiceAssignmentsDistinct(): void
    {
        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user);
        $dayService = DayService::factory()->create(['name' => 'Bereitschaft']);

        $user->dayServices()->attach($dayService->id, ['date' => '2026-07-15']);
        $user->dayServices()->attach($dayService->id, ['date' => '2026-07-15']);

        $assignmentIds = $user->dayServices()->withPivot('id')->get()->pluck('pivot.id');
        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));

        foreach ($assignmentIds as $assignmentId) {
            $response->assertSee('UID:day-service-' . $assignmentId, false);
        }

        $this->assertSame(2, substr_count($response->getContent(), 'SUMMARY:Tagesdienst: Bereitschaft'));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createIndividualTime(User $user, array $attributes): IndividualTime
    {
        return IndividualTime::query()->forceCreate(array_merge([
            'timeable_type' => User::class,
            'timeable_id' => $user->id,
            'break_minutes' => 0,
            'working_time_minutes' => 60,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createCalendarAbo(User $user, array $attributes = []): UserShiftCalendarAbo
    {
        return UserShiftCalendarAbo::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'calendar_abo_id' => fake()->uuid(),
            'date_range' => false,
            'specific_crafts' => false,
            'craft_ids' => [],
            'enable_notification' => false,
        ], $attributes));
    }
}
