<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectDayAssignmentService;
use Artwork\Modules\Shift\Exports\Support\ShiftActivityPresenter;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Artwork\Modules\Shift\Support\ShiftHistoryDayEntryPerson;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Abwesenheiten, Verfügbarkeiten und Notizen zum Tag einer Person erscheinen im Schichtverlauf als
 * eigene Vorgänge (wer, wann, was), sobald eine Person gewählt ist — z. B. aus der Dienstplan-Zelle
 * „Schichtverlauf für diese Person und diesen Tag“.
 */
final class ShiftHistoryDayEntriesTest extends FeatureTestCase
{
    private const DAY = '2026-05-06';

    private function vacationFor(User $person, array $attributes = []): Vacation
    {
        return Vacation::factory()->create(array_merge([
            'vacationer_type' => User::class,
            'vacationer_id' => $person->id,
            'date' => self::DAY,
            'full_day' => true,
            'is_series' => false,
            'type' => 'NOT_AVAILABLE',
            'comment' => 'Arzttermin',
        ], $attributes));
    }

    private function noteFor(User $person, string $text, string $date = self::DAY): ShiftPlanComment
    {
        return $person->shiftPlanComments()->create(['comment' => $text, 'date' => $date]);
    }

    /** @return array<string, mixed> */
    private function historyQuery(User $person, string $from = self::DAY, string $to = self::DAY): array
    {
        return [
            'start_date' => $from,
            'end_date' => $to,
            'person_type' => 'user',
            'person_id' => $person->id,
            'person_scope' => 'assigned',
        ];
    }

    private function dayEntryLogs(): \Illuminate\Database\Eloquent\Builder
    {
        return Activity::query()->whereIn('subject_type', [
            Vacation::class,
            Availability::class,
            ShiftPlanComment::class,
        ]);
    }

    #[Test]
    public function entering_an_absence_logs_who_entered_it_for_whom_and_which_day(): void
    {
        $planner = $this->actingAsAdmin();
        $person = User::factory()->create();

        $this->vacationFor($person);

        $log = $this->dayEntryLogs()->sole();
        $this->assertSame('created', $log->event);
        $this->assertSame($planner->id, (int) $log->causer_id);
        $this->assertSame(User::class, $log->properties['employable_type']);
        $this->assertSame($person->id, $log->properties['employable_id']);
        $this->assertSame(self::DAY, $log->properties['worker_day']);
        $this->assertSame('absence', $log->properties['day_entry']['kind']);
        $this->assertSame('NOT_AVAILABLE', $log->properties['day_entry']['type']);
        $this->assertTrue($log->properties['day_entry']['full_day']);
    }

    #[Test]
    public function notes_are_logged_when_added_changed_and_removed(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();

        $note = $this->noteFor($person, 'Kommt später');
        $note->update(['comment' => 'Kommt eine Stunde später']);
        $note->delete();

        $logs = $this->dayEntryLogs()->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertSame('note', $logs[1]->properties['day_entry']['kind']);
        $this->assertSame('Kommt eine Stunde später', $logs[1]->properties['day_entry']['comment']);
        $this->assertSame(self::DAY, $logs[2]->properties['worker_day']);
    }

    #[Test]
    public function linking_an_absence_to_its_series_is_not_a_separate_entry(): void
    {
        $this->actingAsAdmin();
        $vacation = $this->vacationFor(User::factory()->create(), ['is_series' => false]);

        $vacation->update(['series_id' => 5, 'is_series' => true]);

        $this->assertSame(1, $this->dayEntryLogs()->count());
    }

    #[Test]
    public function the_history_of_a_person_and_day_shows_shift_and_day_entries_in_order(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        $other = User::factory()->create();
        $shift = Shift::factory()->create([
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => self::DAY,
            'end_date' => self::DAY,
        ]);
        activity('shift')->performedOn($shift)->withProperties([
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => [$person->full_name, 'Tech', 'Stage', 'ST'],
            'employable_type' => User::class,
            'employable_id' => $person->id,
        ])->log('User assigned to shift');

        $this->vacationFor($person);
        Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => $person->id,
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '18:00',
            'end_time' => '22:00',
            'comment' => 'nur abends',
        ]);
        $this->noteFor($person, 'Bitte nicht vor 18 Uhr');
        // Andere Person bzw. anderer Tag gehören nicht dazu
        $this->vacationFor($other);
        $this->noteFor($person, 'Anderer Tag', '2026-05-07');

        $response = $this->getJson(route('shift.history.index', $this->historyQuery($person)))->assertOk();

        $logs = collect($response->json('logs.data'));
        $this->assertSame(4, $response->json('logs.meta.total'));
        $this->assertEqualsCanonicalizing(
            [Shift::class, Vacation::class, Availability::class, ShiftPlanComment::class],
            $logs->pluck('subject_type')->all()
        );
        $dayEntry = $logs->firstWhere('subject_type', Vacation::class);
        $this->assertSame(['subject'], $dayEntry['match_reasons']);
        $this->assertSame('Arzttermin', data_get($dayEntry, 'properties.day_entry.comment'));
        $this->assertNotNull(data_get($dayEntry, 'causer.full_name'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        ShiftHistoryDayEntryPerson::flush();
    }

    #[Test]
    public function the_history_of_a_day_without_person_shows_the_day_entries_of_everyone(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create(['first_name' => 'Lisa', 'last_name' => 'Müller']);
        $other = User::factory()->create();
        $this->vacationFor($person);
        $this->noteFor($other, 'Kommt später');
        // Tage davor und danach gehören nicht dazu
        $this->noteFor($other, 'Anderer Tag', '2026-05-07');
        $this->noteFor($other, 'Vortag', '2026-05-05');

        $response = $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 2);

        $vacationLog = collect($response->json('logs.data'))->firstWhere('subject_type', Vacation::class);
        $this->assertSame('Lisa Müller', $vacationLog['day_entry_person_name']);
        $this->assertSame([], $vacationLog['match_reasons']);
    }

    #[Test]
    public function the_craft_filter_limits_day_entries_to_the_people_of_the_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $member = User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($member->id);
        $assigned = User::factory()->create();
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => self::DAY,
            'end_date' => self::DAY,
        ]);
        $shift->users()->attach($assigned->id, [
            'shift_qualification_id' => \Artwork\Modules\Shift\Models\ShiftQualification::factory()->create()->id,
        ]);
        $this->vacationFor($member);
        $this->vacationFor($assigned);
        $this->vacationFor(User::factory()->create());
        Activity::query()->where('subject_type', Shift::class)->delete();

        $response = $this->getJson(route('shift.history.index', [
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'craftId' => $craft->id,
        ]))->assertOk();

        $this->assertSame(2, $response->json('logs.meta.total'));
        $this->assertEqualsCanonicalizing(
            [$member->id, $assigned->id],
            collect($response->json('logs.data'))->pluck('properties.employable_id')->all()
        );
    }

    #[Test]
    public function individual_times_are_logged_and_count_on_each_of_their_days(): void
    {
        $planner = $this->actingAsAdmin();
        $person = User::factory()->create();

        $time = IndividualTime::factory()->create([
            'timeable_type' => User::class,
            'timeable_id' => $person->id,
            'title' => 'Aufbau',
            'start_date' => '2026-05-05',
            'end_date' => self::DAY,
            'start_time' => '22:00',
            'end_time' => '02:00',
            'full_day' => false,
        ]);
        // wie im Dienstplan: Bearbeiten lädt die Zeit frisch (inkl. DB-Defaults)
        $time->refresh();
        $time->update(['title' => 'Aufbau Bühne']);
        // Freigabe-Spalten sind kein eigener Vorgang
        $time->update(['in_workflow' => true]);
        $time->delete();

        $logs = Activity::query()->where('subject_type', IndividualTime::class)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertSame($planner->id, (int) $logs[0]->causer_id);
        $this->assertSame(User::class, $logs[0]->properties['employable_type']);
        $this->assertSame($person->id, $logs[0]->properties['employable_id']);
        $this->assertSame('2026-05-05', $logs[0]->properties['worker_day']);
        $this->assertSame(self::DAY, $logs[0]->properties['worker_day_end']);
        $this->assertSame('individual_time', $logs[0]->properties['day_entry']['kind']);
        $this->assertSame('22:00', $logs[0]->properties['day_entry']['start_time']);
        $this->assertSame('Aufbau Bühne', $logs[1]->properties['day_entry']['title']);

        // Tagesverlauf des zweiten Tags findet die über Mitternacht laufende Zeit
        $this->getJson(route('shift.history.index', $this->historyQuery($person)))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 3);

        $presenter = new ShiftActivityPresenter('en');
        $this->assertSame('Other day entries', $presenter->categoryLabel($logs[2]));
        $this->assertSame('Individual time removed: Aufbau Bühne · 22:00 – 02:00', $presenter->message($logs[2]));
        $this->assertSame('05.05.2026 – 06.05.2026', $presenter->shiftDetails($logs[2], [])['date']);
    }

    #[Test]
    public function a_granted_compensation_day_appears_on_its_day(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create(['first_name' => 'Ben', 'last_name' => 'Kraus']);
        $compensation = CompensationDayOff::query()->create([
            'user_id' => $person->id,
            'value' => 0.5,
            'half_day_period' => 'morning',
            'deadline' => '2026-06-30',
        ]);
        // Guthaben ohne Tag gehört in keinen Tagesverlauf
        $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertJsonPath('logs.meta.total', 0);

        $compensation->update(['granted_date' => self::DAY]);

        $response = $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 1);
        $log = Activity::query()->findOrFail($response->json('logs.data.0.id'));
        $this->assertSame('compensation', $log->properties['day_entry']['kind']);

        $presenter = new ShiftActivityPresenter('en', true);
        $this->assertSame('Ben Kraus: Compensation day off changed: Morning', $presenter->message($log));
        $this->assertSame([], $presenter->changeLines($log));
    }

    #[Test]
    public function project_assignments_carry_person_and_days(): void
    {
        $planner = $this->actingAsAdmin();
        $person = User::factory()->create(['can_work_shifts' => true]);
        $project = Project::factory()->create(['name' => 'Faust']);
        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => '2026-05-01 10:00:00',
            'end_time' => '2026-05-10 18:00:00',
        ]);

        app(ProjectDayAssignmentService::class)->createAssignments(
            $project,
            User::class,
            $person->id,
            ProjectDayAssignmentType::BINDING,
            ['2026-05-05', self::DAY],
            false
        );

        $log = Activity::query()->where('log_name', 'project_day_assignment')->sole();
        $this->assertSame($planner->id, (int) $log->causer_id);
        $this->assertSame($person->id, $log->properties['employable_id']);
        $this->assertSame('2026-05-05', $log->properties['worker_day']);
        $this->assertSame(self::DAY, $log->properties['worker_day_end']);
        $this->assertSame('Faust', $log->properties['day_entry']['project']);

        $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 1)
            ->assertJsonPath('logs.data.0.log_name', 'project_day_assignment');
    }

    #[Test]
    public function assigning_and_removing_a_day_service_shows_up_in_the_day_history(): void
    {
        $planner = $this->actingAsAdmin();
        $person = User::factory()->create(['first_name' => 'Jana', 'last_name' => 'Block']);
        $dayService = DayService::factory()->create(['name' => 'Abenddienst']);

        $attach = fn () => $this->post(route('day-service.attach', [$dayService, $person->id]), [
            'modelType' => 'user',
            'date' => self::DAY,
        ])->assertOk();
        $attach();
        // Doppelklick: keine zweite Zuweisung, kein zweiter Eintrag
        $attach();
        $remove = fn () => $this->patch(route('remove.day.service.from.user', $person->id), [
            'modelType' => 'user',
            'date' => self::DAY,
            'dayService' => $dayService->id,
        ])->assertOk();
        $remove();
        // nichts mehr zu entfernen: kein Eintrag
        $remove();

        $logs = Activity::query()->where('log_name', 'day_service')->orderBy('id')->get();
        $this->assertSame(['created', 'deleted'], $logs->pluck('event')->all());
        $this->assertSame($planner->id, (int) $logs[0]->causer_id);
        $this->assertSame($person->id, $logs[0]->properties['employable_id']);
        $this->assertSame(self::DAY, $logs[0]->properties['worker_day']);
        $this->assertSame('Abenddienst', $logs[0]->properties['day_entry']['name']);

        $response = $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 2);
        $this->assertSame('Jana Block', $response->json('logs.data.0.day_entry_person_name'));
        $this->getJson(route('shift.history.index', $this->historyQuery($person)))
            ->assertJsonPath('logs.meta.total', 2);

        $presenter = new ShiftActivityPresenter('en', true);
        $this->assertSame('Other day entries', $presenter->categoryLabel($logs[1]));
        $this->assertSame('Jana Block: Day service removed: Abenddienst', $presenter->message($logs[1]));
        $this->assertSame('', $presenter->shiftDetails($logs[1], [])['time']);
    }

    #[Test]
    public function deleting_a_day_service_logs_the_future_assignments_it_removes(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        $dayService = DayService::factory()->create(['name' => 'Frühdienst']);
        $person->dayServices()->attach($dayService->id, ['date' => now()->addDays(3)->toDateString()]);
        $person->dayServices()->attach($dayService->id, ['date' => now()->subDays(3)->toDateString()]);

        $this->delete(route('day-service.destroy', $dayService));

        $log = Activity::query()->where('log_name', 'day_service')->sole();
        $this->assertSame('deleted', $log->event);
        $this->assertSame(now()->addDays(3)->toDateString(), $log->properties['worker_day']);
    }

    #[Test]
    public function day_entries_are_left_out_when_a_single_shift_is_filtered(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        $this->vacationFor($person);

        $shift = Shift::factory()->create(['start_date' => self::DAY, 'end_date' => self::DAY]);
        $this->getJson(route('shift.history.index', [...$this->historyQuery($person), 'shiftId' => $shift->id]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 0);
    }

    #[Test]
    public function older_entries_without_person_fields_are_found_through_the_logged_attributes(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        $vacation = $this->vacationFor($person);
        // Stand vor dieser Erweiterung: nur attributes bzw. old
        $this->dayEntryLogs()->delete();
        activity('vacation')->performedOn($vacation)->event('created')->withProperties([
            'attributes' => ['date' => self::DAY, 'vacationer_type' => User::class, 'vacationer_id' => $person->id,
                'type' => 'OFF_WORK', 'full_day' => true],
        ])->log('created');
        activity('vacation')->performedOn($vacation)->event('deleted')->withProperties([
            'old' => ['date' => self::DAY, 'vacationer_type' => User::class, 'vacationer_id' => $person->id,
                'type' => 'OFF_WORK', 'full_day' => true],
        ])->log('deleted');

        $query = [...$this->historyQuery($person), 'sort' => 'shift_day'];
        $response = $this->getJson(route('shift.history.index', $query))->assertOk();

        $this->assertSame(2, $response->json('logs.meta.total'));
        $this->assertSame(['subject'], $response->json('logs.data.0.match_reasons'));
    }

    #[Test]
    public function export_describes_day_entries_without_shift_data(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        // Gleiche ID wie die Abwesenheit: darf nicht als Schicht gelesen werden
        $vacation = $this->vacationFor($person, ['full_day' => false, 'start_time' => '08:00', 'end_time' => '12:00']);
        $note = $this->noteFor($person, 'Kommt später');

        $presenter = new ShiftActivityPresenter('en');
        $vacationLog = $this->dayEntryLogs()->where('subject_type', Vacation::class)->sole();
        $noteLog = $this->dayEntryLogs()->where('subject_type', ShiftPlanComment::class)->sole();

        $this->assertSame('Availability & absence', $presenter->categoryLabel($vacationLog));
        $this->assertSame(
            'Absence entered: Not available · 08:00 – 12:00 · „Arzttermin“',
            $presenter->message($vacationLog)
        );
        $this->assertSame(
            ['date' => '06.05.2026', 'time' => '08:00 – 12:00', 'room' => '', 'craft' => ''],
            $presenter->shiftDetails($vacationLog, [$vacation->id => Shift::factory()->make(['start' => '01:00'])])
        );
        $this->assertSame('Notes', $presenter->categoryLabel($noteLog));
        $this->assertSame('Note added: „Kommt später“', $presenter->message($noteLog));
        $this->assertSame($note->id, (int) $noteLog->subject_id);
    }
}
