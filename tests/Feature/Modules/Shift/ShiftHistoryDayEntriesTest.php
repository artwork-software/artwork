<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Exports\Support\ShiftActivityPresenter;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanComment;
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

    #[Test]
    public function day_entries_need_a_selected_person_and_no_single_shift_filter(): void
    {
        $this->actingAsAdmin();
        $person = User::factory()->create();
        $this->vacationFor($person);

        $this->getJson(route('shift.history.index', ['start_date' => self::DAY, 'end_date' => self::DAY]))
            ->assertOk()
            ->assertJsonPath('logs.meta.total', 0);

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
