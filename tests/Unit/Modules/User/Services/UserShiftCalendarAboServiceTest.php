<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Services\UserShiftCalendarAboService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Spatie\IcalendarGenerator\Components\Calendar;
use Tests\TestCase;

final class UserShiftCalendarAboServiceTest extends TestCase
{
    private UserShiftCalendarAboService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserShiftCalendarAboService::class);
    }

    private function defaultPayload(): array
    {
        return [
            'date_range' => false,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
            'specific_crafts' => false,
            'craft_ids' => [],
            'enable_notification' => false,
            'notification_time' => 0,
            'notification_time_unit' => 'minutes',
        ];
    }

    #[Test]
    public function create_persists_shift_calendar_abo(): void
    {
        $user = User::factory()->create();

        $this->service->create($this->defaultPayload(), $user->id);

        $this->assertDatabaseHas('user_shift_calendar_abos', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function update_by_request_modifies_abo(): void
    {
        $user = User::factory()->create();
        $this->service->create($this->defaultPayload(), $user->id);
        $abo = UserShiftCalendarAbo::first();

        $this->service->updateByRequest($abo, ['notification_time' => 30, 'enable_notification' => true]);

        $fresh = $abo->fresh();
        $this->assertSame(30, $fresh->notification_time);
        $this->assertTrue((bool) $fresh->enable_notification);
    }

    #[Test]
    public function should_add_shift_returns_true_when_no_craft_restriction(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->specific_crafts = false;

        $shift = (object) ['craft_id' => 1];

        $this->assertTrue($this->service->shouldAddShift($abo, $shift));
    }

    #[Test]
    public function should_add_shift_filters_by_craft_ids(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->specific_crafts = true;
        $abo->craft_ids = [1, 2, 3];

        $this->assertTrue($this->service->shouldAddShift($abo, (object) ['craft_id' => 2]));
        $this->assertFalse($this->service->shouldAddShift($abo, (object) ['craft_id' => 99]));
    }

    #[Test]
    public function get_filtered_shifts_returns_sorted_collection(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->date_range = false;

        $shifts = new Collection([
            (object) ['start_date' => '2024-05-10', 'is_committed' => true],
            (object) ['start_date' => '2024-05-01', 'is_committed' => true],
        ]);

        $filtered = $this->service->getFilteredShifts($abo, $shifts);

        $this->assertSame('2024-05-01', $filtered->first()->start_date);
    }

    #[Test]
    public function add_shift_to_calendar_includes_project_and_room_of_event_less_shift(): void
    {
        $room = Room::factory()->create(['name' => 'Abo-Testraum']);
        $project = Project::factory()->create(['name' => 'Abo-Testprojekt']);
        $shift = Shift::factory()->create([
            'event_id' => null,
            'craft_id' => Craft::factory(),
            'room_id' => $room->id,
            'project_id' => $project->id,
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        $abo = new UserShiftCalendarAbo();
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addShiftToCalendar($calendar, $abo, $shift->fresh());

        // ICS-Zeilenfaltung (RFC 5545) rückgängig machen, sonst kann der
        // gesuchte Name mitten im Wort umbrochen sein
        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('Abo-Testprojekt', $ics);
        $this->assertStringContainsString('Abo-Testraum', $ics);
    }

    #[Test]
    public function add_shift_to_calendar_titles_with_craft_abbreviation_and_project_name(): void
    {
        $craft = Craft::factory()->create(['name' => 'Beleuchtung', 'abbreviation' => 'LX']);
        $shift = Shift::factory()->create([
            'event_id' => null,
            'craft_id' => $craft->id,
            'room_id' => Room::factory(),
            'project_id' => Project::factory()->create(['name' => 'La Horde'])->id,
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        $abo = new UserShiftCalendarAbo();
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addShiftToCalendar($calendar, $abo, $shift->fresh());

        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('SUMMARY:LX - La Horde', $ics);
        // Uhrzeit gehört nicht mehr in den Titel
        $this->assertStringNotContainsString('SUMMARY:Schicht: Beleuchtung - 08:00', $ics);
    }

    #[Test]
    public function add_shift_to_calendar_lists_colleagues_in_description_without_abo_owner(): void
    {
        $aboOwner = User::factory()->create(['first_name' => 'Abo', 'last_name' => 'Inhaberin']);
        $colleague = User::factory()->create(['first_name' => 'Kai', 'last_name' => 'Kollege']);
        $shift = Shift::factory()->create([
            'event_id' => null,
            'craft_id' => Craft::factory(),
            'room_id' => Room::factory(),
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);
        $qualificationId = ShiftQualification::factory()->create()->id;
        $aboOwner->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);
        $colleague->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);

        $abo = new UserShiftCalendarAbo();
        $abo->user_id = $aboOwner->id;
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addShiftToCalendar($calendar, $abo, $aboOwner->shifts()->first());

        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('Mit: Kai Kollege', $ics);
        $this->assertStringNotContainsString('Mit: Abo Inhaberin', $ics);
    }

    #[Test]
    public function add_shift_to_calendar_omits_attendees_and_organizer(): void
    {
        $creator = User::factory()->create([
            'first_name' => 'Orga',
            'last_name' => 'Nisator',
            'email' => 'orga@example.test',
        ]);
        $aboOwner = User::factory()->create(['first_name' => 'Abo', 'last_name' => 'Inhaberin']);
        $colleague = User::factory()->create([
            'first_name' => 'Kai',
            'last_name' => 'Kollege',
            'email' => 'kollege@example.test',
        ]);
        $freelancer = Freelancer::factory()->create([
            'first_name' => 'Frei',
            'last_name' => 'Lancer',
            'email' => 'freelancer@example.test',
        ]);
        $serviceProvider = ServiceProvider::factory()->create([
            'provider_name' => 'Dienstleister GmbH',
            'email' => 'dienstleister@example.test',
        ]);
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => Craft::factory(),
            'room_id' => Room::factory(),
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        $qualificationId = ShiftQualification::factory()->create()->id;
        $aboOwner->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);
        $colleague->shifts()->attach($shift->id, ['shift_qualification_id' => $qualificationId]);
        $shift->freelancer()->attach($freelancer->id, ['shift_qualification_id' => $qualificationId]);
        $shift->serviceProvider()->attach($serviceProvider->id, ['shift_qualification_id' => $qualificationId]);

        $abo = new UserShiftCalendarAbo();
        $abo->user_id = $aboOwner->id;
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addShiftToCalendar($calendar, $abo, $aboOwner->shifts()->first());

        $ics = str_replace("\r\n ", '', $calendar->get());

        // Keine Einladungs-Properties: Google & Co. laden sonst beim Import
        // alle hinterlegten Adressen als Gaeste ein
        $this->assertStringNotContainsString('ATTENDEE', $ics);
        $this->assertStringNotContainsString('ORGANIZER', $ics);
        $this->assertStringNotContainsString('orga@example.test', $ics);
        $this->assertStringNotContainsString('kollege@example.test', $ics);
        $this->assertStringNotContainsString('freelancer@example.test', $ics);
        $this->assertStringNotContainsString('dienstleister@example.test', $ics);

        // Die Personen bleiben namentlich in der Beschreibung erhalten
        $this->assertStringContainsString('Kai Kollege', $ics);
        $this->assertStringContainsString('Frei Lancer', $ics);
        $this->assertStringContainsString('Dienstleister GmbH', $ics);
        $this->assertStringContainsString('Organisation: Orga Nisator', $ics);
    }

    #[Test]
    public function add_shift_to_calendar_prefers_individual_pivot_times_over_shift_times(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->create([
            'event_id' => null,
            'craft_id' => Craft::factory(),
            'room_id' => Room::factory(),
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);
        $user->shifts()->attach($shift->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'start_date' => '2024-05-10',
            'end_date' => '2024-05-10',
            'start_time' => '10:00',
            'end_time' => '12:15',
        ]);

        $abo = new UserShiftCalendarAbo();
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        // Über die Relation laden, damit das Pivot (shift_workers) dranhängt
        $this->service->addShiftToCalendar($calendar, $abo, $user->shifts()->first());

        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('20240510T100000', $ics);
        $this->assertStringContainsString('20240510T121500', $ics);
        $this->assertStringNotContainsString('20240510T080000', $ics);
        $this->assertStringNotContainsString('20240510T160000', $ics);
    }

    #[Test]
    public function add_individual_time_to_calendar_includes_title_and_times(): void
    {
        $user = User::factory()->create();
        $individualTime = $user->individualTimes()->create([
            'title' => 'Individuelle-Zeit-Notiz',
            'start_date' => '2024-05-15',
            'end_date' => '2024-05-15',
            'start_time' => '09:30',
            'end_time' => '14:00',
            'full_day' => false,
        ]);

        $abo = new UserShiftCalendarAbo();
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addIndividualTimeToCalendar($calendar, $abo, $individualTime->fresh());

        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('Individuelle-Zeit-Notiz', $ics);
        $this->assertStringContainsString('20240515T093000', $ics);
        $this->assertStringContainsString('20240515T140000', $ics);
    }

    #[Test]
    public function add_individual_time_to_calendar_renders_full_day_entries_as_all_day_events(): void
    {
        $user = User::factory()->create();
        $individualTime = $user->individualTimes()->create([
            'title' => 'Ganztags-Eintrag',
            'start_date' => '2024-05-20',
            'end_date' => '2024-05-21',
            'full_day' => true,
        ]);

        $abo = new UserShiftCalendarAbo();
        $abo->enable_notification = false;

        $calendar = Calendar::create('Test');
        $this->service->addIndividualTimeToCalendar($calendar, $abo, $individualTime->fresh());

        $ics = str_replace("\r\n ", '', $calendar->get());
        $this->assertStringContainsString('Ganztags-Eintrag', $ics);
        $this->assertStringContainsString('VALUE=DATE:20240520', $ics);
        // DTEND ist bei Ganztagesterminen exklusiv (letzter Tag + 1)
        $this->assertStringContainsString('VALUE=DATE:20240522', $ics);
    }

    #[Test]
    public function get_filtered_individual_times_respects_date_range(): void
    {
        $abo = new UserShiftCalendarAbo();
        $abo->date_range = true;
        $abo->start_date = '2024-05-01';
        $abo->end_date = '2024-05-31';

        $individualTimes = new Collection([
            (object) ['start_date' => '2024-05-10', 'end_date' => '2024-05-10'],
            (object) ['start_date' => '2024-06-05', 'end_date' => '2024-06-05'],
        ]);

        $filtered = $this->service->getFilteredIndividualTimes($abo, $individualTimes);

        $this->assertCount(1, $filtered);
        $this->assertSame('2024-05-10', $filtered->first()->start_date);
    }
}
