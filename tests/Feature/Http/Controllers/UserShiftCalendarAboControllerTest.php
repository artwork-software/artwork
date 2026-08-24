<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Illuminate\Support\Str;
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

    #[Test]
    public function shiftCalendarFeedContainsNoAttendeesOrganizerOrEmailAddresses(): void
    {
        $creator = User::factory()->create([
            'first_name' => 'Orga',
            'last_name' => 'Nisator',
            'email' => 'orga@example.test',
        ]);
        $user = User::factory()->create();
        $colleague = User::factory()->create([
            'first_name' => 'Kai',
            'last_name' => 'Kollege',
            'email' => 'kollege@example.test',
        ]);
        $freelancer = Freelancer::factory()->create(['email' => 'freelancer@example.test']);
        $serviceProvider = ServiceProvider::factory()->create(['email' => 'dienstleister@example.test']);
        $calendarAbo = $this->createCalendarAbo($user);

        $event = Event::factory()->create(['user_id' => $creator->id]);
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => Craft::factory(),
            'room_id' => Room::factory(),
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start' => '08:00',
            'end' => '16:00',
            'is_committed' => true,
        ]);

        $qualificationId = ShiftQualification::factory()->create()->id;
        $user->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);
        $colleague->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);
        $shift->freelancer()->attach($freelancer->id, ['shift_qualification_id' => $qualificationId]);
        $shift->serviceProvider()->attach($serviceProvider->id, ['shift_qualification_id' => $qualificationId]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));

        $response->assertOk();
        $ics = str_replace("\r\n ", '', $response->getContent());

        // Schicht ist wirklich im Feed — sonst wuerden die Assertions unten
        // auch bei einem leeren Kalender gruen sein
        $this->assertStringContainsString('UID:shift-' . $shift->id, $ics);

        // Kein Einladungs-Verhalten beim Import (Google & Co.)
        $this->assertStringContainsString('METHOD:PUBLISH', $ics);
        $this->assertStringNotContainsString('ATTENDEE', $ics);
        $this->assertStringNotContainsString('ORGANIZER', $ics);
        $this->assertStringNotContainsString('orga@example.test', $ics);
        $this->assertStringNotContainsString('kollege@example.test', $ics);
        $this->assertStringNotContainsString('freelancer@example.test', $ics);
        $this->assertStringNotContainsString('dienstleister@example.test', $ics);

        // Namen bleiben erhalten
        $this->assertStringContainsString('Mit: Kai Kollege', $ics);
        $this->assertStringContainsString('Organisation: Orga Nisator', $ics);
    }

    #[Test]
    public function updateCannotReassignTheSubscriptionToAnotherUser(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($attacker);

        $this->actingAs($attacker)
            ->patch(
                route('user.shift.calendar.abo.update', $calendarAbo->id),
                ['user_id' => $victim->id]
            );

        $this->assertSame($attacker->id, $calendarAbo->fresh()->user_id);
    }

    #[Test]
    public function updateOfAForeignSubscriptionIsForbidden(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($victim, ['specific_crafts' => false]);

        $response = $this->actingAs($attacker)
            ->patch(
                route('user.shift.calendar.abo.update', $calendarAbo->id),
                ['specific_crafts' => true]
            );

        $response->assertForbidden();
        $this->assertFalse((bool) $calendarAbo->fresh()->specific_crafts);
        $this->assertSame($victim->id, $calendarAbo->fresh()->user_id);
    }

    #[Test]
    public function updateCannotOverwriteTheFeedUuid(): void
    {
        $owner = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($owner);
        $originalUuid = $calendarAbo->calendar_abo_id;

        $this->actingAs($owner)
            ->patch(
                route('user.shift.calendar.abo.update', $calendarAbo->id),
                ['calendar_abo_id' => 'attacker-chosen-uuid']
            );

        $this->assertSame($originalUuid, $calendarAbo->fresh()->calendar_abo_id);
        $this->get(route('user-shift-calendar-abo.show', 'attacker-chosen-uuid'))->assertNotFound();
    }

    #[Test]
    public function storeAlwaysGeneratesTheFeedUuidOnTheServer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('user.shift.calendar.abo.create'), [
            'calendar_abo_id' => 'attacker-chosen-uuid',
            'user_id' => User::factory()->create()->id,
            'date_range' => false,
            'specific_crafts' => false,
            'craft_ids' => [],
            'enable_notification' => false,
            'notification_time' => 0,
            'notification_time_unit' => 'minutes',
        ]);

        $calendarAbo = UserShiftCalendarAbo::query()->where('user_id', $user->id)->sole();

        $this->assertNotSame('attacker-chosen-uuid', $calendarAbo->calendar_abo_id);
        $this->assertTrue(Str::isUuid($calendarAbo->calendar_abo_id));
        $this->get(route('user-shift-calendar-abo.show', 'attacker-chosen-uuid'))->assertNotFound();
    }

    #[Test]
    public function theOwnerCanStillUpdateTheirOwnSubscription(): void
    {
        $owner = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($owner);
        $craft = Craft::factory()->create();

        $this->actingAs($owner)
            ->patch(route('user.shift.calendar.abo.update', $calendarAbo->id), [
                'id' => $calendarAbo->id,
                'date_range' => true,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
                'specific_crafts' => true,
                'craft_ids' => [$craft->id],
                'enable_notification' => true,
                'notification_time' => 30,
                'notification_time_unit' => 'minutes',
            ]);

        $calendarAbo = $calendarAbo->fresh();

        $this->assertTrue((bool) $calendarAbo->date_range);
        $this->assertTrue((bool) $calendarAbo->specific_crafts);
        $this->assertSame([$craft->id], $calendarAbo->craft_ids);
        $this->assertSame(30, $calendarAbo->notification_time);
        $this->assertSame('minutes', $calendarAbo->notification_time_unit);
        $this->assertSame($owner->id, $calendarAbo->user_id);
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
