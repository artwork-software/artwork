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
use App\Settings\ShiftSettings;
use Carbon\Carbon;
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

        // Zeitgebundene Individualzeiten sind echte Arbeitszeit und dürfen
        // NICHT als "frei" markiert werden
        $response->assertDontSee('TRANSP:TRANSPARENT', false)
            ->assertDontSee('X-MICROSOFT-CDO-BUSYSTATUS:FREE', false);
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
            ->assertSee('SUMMARY:Individuelle Zeit: Fortbildung', false)
            // Ganztägige Einträge sollen im Zielkalender nicht als "beschäftigt" blocken
            ->assertSee('TRANSP:TRANSPARENT', false)
            ->assertSee('X-MICROSOFT-CDO-BUSYSTATUS:FREE', false);

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
            // Tagesdienste sollen im Zielkalender nicht als "beschäftigt" blocken
            ->assertSee('TRANSP:TRANSPARENT', false)
            ->assertSee('X-MICROSOFT-CDO-BUSYSTATUS:FREE', false)
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
            // Relativ zu heute: der Feed liefert nur -30 Tage .. +12 Monate
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
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

    // --- Block 2c: vorläufige Schichten, Widerruf, Ladefenster ------------------------------

    #[Test]
    public function feedMarksUncommittedShiftsAsProvisionalWhenAllShiftsAreShown(): void
    {
        $this->setCalendarAboShowAllShifts(true);

        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user);
        $qualification = ShiftQualification::factory()->create(['name' => 'Operator*in']);

        // Feste Kürzel, damit die SUMMARY-Assertions nicht an ICS-Escaping von Faker-Werten scheitern
        $committed = $this->createShiftForUser($user, $qualification->id, [
            'craft_id' => Craft::factory()->create(['name' => 'Licht', 'abbreviation' => 'FIX'])->id,
            'start_date' => Carbon::now()->addDays(3)->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'is_committed' => true,
            'break_minutes' => 30,
        ]);
        $provisional = $this->createShiftForUser($user, $qualification->id, [
            'craft_id' => Craft::factory()->create(['name' => 'Ton', 'abbreviation' => 'PROV'])->id,
            'start_date' => Carbon::now()->addDays(4)->toDateString(),
            'end_date' => Carbon::now()->addDays(4)->toDateString(),
            'is_committed' => false,
            'break_minutes' => 45,
        ]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));
        $response->assertOk();
        $ics = str_replace("\r\n ", '', $response->getContent());

        $this->assertStringContainsString('UID:shift-' . $committed->id, $ics);
        $this->assertStringContainsString('UID:shift-' . $provisional->id, $ics);

        // Genau der vorläufige Termin trägt das Präfix im Titel und den Hinweis in der Beschreibung
        $this->assertSame(1, substr_count($ics, 'SUMMARY:[vorläufig] '));
        $this->assertSame(1, substr_count($ics, 'Vorläufig: noch nicht festgeschrieben.'));
        $this->assertStringContainsString('SUMMARY:[vorläufig] PROV', $ics);
        $this->assertStringContainsString('SUMMARY:FIX', $ics);
        $this->assertStringNotContainsString('SUMMARY:[vorläufig] FIX', $ics);

        // Funktion und Pause stehen in der Beschreibung, LAST-MODIFIED ist gesetzt
        $this->assertStringContainsString('Funktion: Operator*in', $ics);
        $this->assertStringContainsString('Pause: 30 min', $ics);
        $this->assertStringContainsString('Pause: 45 min', $ics);
        $this->assertSame(2, substr_count($ics, 'LAST-MODIFIED:'));
    }

    #[Test]
    public function feedOmitsUncommittedShiftsWhenOnlyCommittedShiftsAreShown(): void
    {
        $this->setCalendarAboShowAllShifts(false);

        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user);
        $qualificationId = ShiftQualification::factory()->create()->id;

        $committed = $this->createShiftForUser($user, $qualificationId, [
            'start_date' => Carbon::now()->addDays(3)->toDateString(),
            'end_date' => Carbon::now()->addDays(3)->toDateString(),
            'is_committed' => true,
        ]);
        $provisional = $this->createShiftForUser($user, $qualificationId, [
            'start_date' => Carbon::now()->addDays(4)->toDateString(),
            'end_date' => Carbon::now()->addDays(4)->toDateString(),
            'is_committed' => false,
        ]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));
        $response->assertOk();
        $ics = str_replace("\r\n ", '', $response->getContent());

        $this->assertStringContainsString('UID:shift-' . $committed->id, $ics);
        $this->assertStringNotContainsString('UID:shift-' . $provisional->id, $ics);
        $this->assertStringNotContainsString('[vorläufig]', $ics);
    }

    #[Test]
    public function renewingTheLinkInvalidatesTheOldToken(): void
    {
        $owner = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($owner);
        $oldToken = $calendarAbo->calendar_abo_id;

        $this->get(route('user-shift-calendar-abo.show', $oldToken))->assertOk();

        $this->actingAs($owner)
            ->delete(route('user.shift.calendar.abo.renew', $calendarAbo->id))
            ->assertRedirect();

        $newToken = $calendarAbo->fresh()->calendar_abo_id;
        $this->assertNotSame($oldToken, $newToken);

        // Alter Link 404, neuer Link liefert den Feed; Einstellungen bleiben erhalten
        $this->get(route('user-shift-calendar-abo.show', $oldToken))->assertNotFound();
        $this->get(route('user-shift-calendar-abo.show', $newToken))->assertOk();
        $this->assertSame($owner->id, $calendarAbo->fresh()->user_id);
    }

    #[Test]
    public function aForeignSubscriptionLinkCannotBeRenewed(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($victim);
        $oldToken = $calendarAbo->calendar_abo_id;

        $this->actingAs($attacker)
            ->delete(route('user.shift.calendar.abo.renew', $calendarAbo->id))
            ->assertForbidden();

        $this->assertSame($oldToken, $calendarAbo->fresh()->calendar_abo_id);
    }

    #[Test]
    public function feedOnlyLoadsShiftsWithinTheLoadingWindow(): void
    {
        $this->setCalendarAboShowAllShifts(true);

        $user = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($user);
        $qualificationId = ShiftQualification::factory()->create()->id;

        $tooOld = $this->createShiftForUser($user, $qualificationId, [
            'start_date' => Carbon::now()->subDays(60)->toDateString(),
            'end_date' => Carbon::now()->subDays(60)->toDateString(),
            'is_committed' => true,
        ]);
        $recent = $this->createShiftForUser($user, $qualificationId, [
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDays(10)->toDateString(),
            'is_committed' => true,
        ]);
        $tooFar = $this->createShiftForUser($user, $qualificationId, [
            'start_date' => Carbon::now()->addMonths(14)->toDateString(),
            'end_date' => Carbon::now()->addMonths(14)->toDateString(),
            'is_committed' => true,
        ]);

        $response = $this->get(route('user-shift-calendar-abo.show', $calendarAbo->calendar_abo_id));
        $response->assertOk();
        $ics = str_replace("\r\n ", '', $response->getContent());

        $this->assertStringContainsString('UID:shift-' . $recent->id, $ics);
        $this->assertStringNotContainsString('UID:shift-' . $tooOld->id, $ics);
        $this->assertStringNotContainsString('UID:shift-' . $tooFar->id, $ics);
    }

    private function setCalendarAboShowAllShifts(bool $enabled): void
    {
        $settings = app(ShiftSettings::class);
        $settings->calendar_abo_show_all_shifts = $enabled;
        $settings->save();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createShiftForUser(User $user, int $qualificationId, array $attributes): Shift
    {
        $shift = Shift::factory()->create(array_merge([
            'event_id' => Event::factory()->create()->id,
            'craft_id' => Craft::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
        ], $attributes));

        $user->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);

        return $shift->fresh(['craft']);
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
