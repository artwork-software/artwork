<?php

namespace Tests\Feature\Modules\Shift;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Enums\ShortNoticeReason;
use Artwork\Modules\Shift\Exports\CommittedShiftChangesExcelExport;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kennzeichnung kurzfristiger Änderungen an verbindlichen Diensten (Änderung nach der Stichzeit am
 * Vortag des Dienstbeginns): Markierung beim Entstehen, Grund, Filter und Export-Spalten. Abgeschaltet
 * (Standard) entsteht keine Markierung und die Oberfläche bekommt nichts Zusätzliches.
 */
final class ShortNoticeChangeTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function setMarking(bool $enabled, string $cutoff = '14:00'): void
    {
        $settings = app(ShiftSettings::class);
        $settings->short_notice_marking_enabled = $enabled;
        $settings->short_notice_cutoff_time = $cutoff;
        $settings->save();
    }

    private function shiftOn(string $date, string $start = '10:00', string $end = '18:00'): Shift
    {
        return Shift::factory()->create([
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function recordChange(Shift $shift, array $attributes = []): CommittedShiftChange
    {
        return CommittedShiftChange::create(array_merge([
            'craft_id' => $shift->craft_id,
            'shift_id' => $shift->id,
            'subject_type' => Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => ['break_minutes' => ['old' => 0, 'new' => 30]],
            'changed_at' => now(),
        ], $attributes));
    }

    #[Test]
    public function nothing_is_marked_while_the_setting_is_off(): void
    {
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-10 18:00:00');

        $this->assertFalse($this->recordChange($shift)->is_short_notice);
    }

    #[Test]
    public function change_before_the_cutoff_on_the_day_before_is_not_short_notice(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-10 13:59:00');
        $this->assertFalse($this->recordChange($shift)->is_short_notice);

        Carbon::setTestNow('2026-03-10 14:00:00');
        $this->assertFalse($this->recordChange($shift)->is_short_notice, 'Bis 14 Uhr ist die Frist gewahrt');
    }

    #[Test]
    public function change_after_the_cutoff_on_the_day_before_is_short_notice(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-10 14:01:00');
        $this->assertTrue($this->recordChange($shift)->is_short_notice);
    }

    #[Test]
    public function change_on_the_day_of_the_shift_is_short_notice(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-11 08:00:00');
        $this->assertTrue($this->recordChange($shift)->is_short_notice);

        Carbon::setTestNow('2026-03-11 21:30:00');
        $this->assertTrue($this->recordChange($shift)->is_short_notice, 'Auch während und nach dem Dienst am selben Tag');
    }

    #[Test]
    public function later_corrections_of_past_shifts_are_not_short_notice(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-12 09:00:00');
        $this->assertFalse($this->recordChange($shift)->is_short_notice);
    }

    #[Test]
    public function the_cutoff_time_follows_the_setting(): void
    {
        $this->setMarking(true, '12:00');
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-10 12:30:00');
        $this->assertTrue($this->recordChange($shift)->is_short_notice);
    }

    #[Test]
    public function the_start_before_the_change_decides(): void
    {
        $this->setMarking(true);
        // Dienst lag am 11.03. und wurde gerade auf den 20.03. verschoben: Frist hängt am alten Beginn
        $shift = $this->shiftOn('2026-03-20');
        Carbon::setTestNow('2026-03-10 16:00:00');

        $change = $this->recordChange($shift, [
            'field_changes' => ['start_date' => ['old' => '2026-03-11', 'new' => '2026-03-20']],
        ]);

        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function moving_a_shift_forward_counts_from_the_earlier_new_start(): void
    {
        $this->setMarking(true);
        // Dienst lag am 20.03. und wurde auf morgen vorverlegt
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-10 16:00:00');

        $change = $this->recordChange($shift, [
            'field_changes' => ['start_date' => ['old' => '2026-03-20', 'new' => '2026-03-11']],
        ]);

        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function a_time_change_recorded_with_full_timestamps_uses_the_old_start(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11', '12:00');
        Carbon::setTestNow('2026-03-10 15:00:00');

        $change = $this->recordChange($shift, [
            'change_type' => 'shift_time_updated',
            'field_changes' => [
                'start' => ['old' => '2026-03-11 10:00:00', 'new' => '2026-03-11 12:00:00'],
            ],
        ]);

        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function a_deleted_shift_is_judged_by_its_last_state(): void
    {
        $this->setMarking(true);
        Carbon::setTestNow('2026-03-10 15:00:00');

        $change = CommittedShiftChange::create([
            'shift_id' => null,
            'subject_type' => Shift::class,
            'subject_id' => 999,
            'change_type' => 'deleted',
            'field_changes' => ['_deleted' => ['start_date' => '2026-03-11', 'start' => '10:00']],
            'changed_at' => now(),
        ]);

        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function changed_individual_times_are_marked_as_well(): void
    {
        $this->setMarking(true);
        Carbon::setTestNow('2026-03-10 15:00:00');

        $change = CommittedShiftChange::create([
            'subject_type' => IndividualTime::class,
            'subject_id' => 1,
            'change_type' => 'individual_time_updated',
            'field_changes' => [
                'start_time' => ['old' => '09:00', 'new' => '11:00'],
                '_individual_time' => ['start_date' => '2026-03-11', 'start_time' => '11:00', 'full_day' => false],
            ],
            'changed_at' => now(),
        ]);

        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function editing_a_committed_shift_marks_the_recorded_change(): void
    {
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');
        $shift->forceFill(['is_committed' => true])->saveQuietly();

        Carbon::setTestNow('2026-03-10 17:00:00');
        $shift->refresh()->update(['break_minutes' => 45]);

        $change = CommittedShiftChange::query()->where('shift_id', $shift->id)->latest('id')->first();
        $this->assertNotNull($change, 'Änderung an verbindlicher Schicht wurde nicht protokolliert');
        $this->assertTrue($change->is_short_notice);
    }

    #[Test]
    public function reason_and_note_can_be_recorded(): void
    {
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-10 17:00:00');
        $change = $this->recordChange($shift);

        $this->patch(route('committed-shift-changes.short-notice-reason', $change), [
            'reason' => ShortNoticeReason::MutualConsent->value,
            'note' => 'Mit der Kollegin abgesprochen',
        ])->assertRedirect();

        $change->refresh();
        $this->assertSame(ShortNoticeReason::MutualConsent, $change->short_notice_reason);
        $this->assertSame('Mit der Kollegin abgesprochen', $change->short_notice_note);

        // Grund wieder entfernen
        $this->patch(route('committed-shift-changes.short-notice-reason', $change), [
            'reason' => null,
            'note' => null,
        ])->assertRedirect();
        $this->assertNull($change->refresh()->short_notice_reason);
    }

    #[Test]
    public function an_unknown_reason_is_rejected(): void
    {
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-10 17:00:00');
        $change = $this->recordChange($shift);

        $this->patch(route('committed-shift-changes.short-notice-reason', $change), ['reason' => 'because'])
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function reason_needs_the_right_to_approve_changes(): void
    {
        $this->setMarking(true);
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-10 17:00:00');
        $change = $this->recordChange($shift);

        $this->patch(route('committed-shift-changes.short-notice-reason', $change), ['reason' => 'other'])
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->patch(route('committed-shift-changes.short-notice-reason', $change), ['reason' => 'other'])
            ->assertForbidden();
        $this->assertNull($change->refresh()->short_notice_reason);
    }

    #[Test]
    public function reason_is_only_possible_for_marked_changes_and_active_setting(): void
    {
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');

        Carbon::setTestNow('2026-03-09 10:00:00');
        $regular = $this->recordChange($shift);
        $this->patch(route('committed-shift-changes.short-notice-reason', $regular), ['reason' => 'other'])
            ->assertNotFound();

        Carbon::setTestNow('2026-03-10 17:00:00');
        $marked = $this->recordChange($shift);
        $this->setMarking(false);
        $this->patch(route('committed-shift-changes.short-notice-reason', $marked), ['reason' => 'other'])
            ->assertNotFound();
    }

    #[Test]
    public function change_overview_offers_the_filter_only_when_enabled(): void
    {
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');

        $this->get(route('shifts.approvals.changes'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shortNotice.enabled', false)
                ->where('shortNotice.reasons', []));

        $this->setMarking(true);
        Carbon::setTestNow('2026-03-09 10:00:00');
        $regular = $this->recordChange($shift);
        Carbon::setTestNow('2026-03-10 17:00:00');
        $marked = $this->recordChange($shift);

        $this->get(route('shifts.approvals.changes', ['short_notice' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shortNotice.enabled', true)
                ->where('shortNotice.only', true)
                ->where('shortNotice.cutoff_time', '14:00')
                ->has('shortNotice.reasons', count(ShortNoticeReason::cases()))
                ->has('changes.data', 1)
                ->where('changes.data.0.id', $marked->id)
                ->where('changes.data.0.is_short_notice', true));

        $this->get(route('shifts.approvals.changes'))
            ->assertInertia(fn ($page) => $page->has('changes.data', 2));
        $this->assertFalse($regular->refresh()->is_short_notice);
    }

    #[Test]
    public function approve_all_respects_the_short_notice_filter(): void
    {
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-09 10:00:00');
        $regular = $this->recordChange($shift);
        Carbon::setTestNow('2026-03-10 17:00:00');
        $marked = $this->recordChange($shift);

        $this->post(route('committed-shift-changes.acknowledge-all'), [
            'craft_id' => $shift->craft_id,
            'short_notice' => 1,
        ])->assertRedirect();

        $this->assertNotNull($marked->refresh()->acknowledged_at);
        $this->assertNull($regular->refresh()->acknowledged_at);
    }

    #[Test]
    public function export_gets_the_columns_only_when_enabled(): void
    {
        $shift = $this->shiftOn('2026-03-11');
        $this->setMarking(true);
        Carbon::setTestNow('2026-03-10 17:00:00');
        $marked = $this->recordChange($shift, [
            'short_notice_reason' => ShortNoticeReason::OperationalDisruption->value,
            'short_notice_note' => 'Stromausfall',
        ]);

        $query = CommittedShiftChange::query()->whereKey($marked->id);

        $plain = new CommittedShiftChangesExcelExport(clone $query, 'de');
        $this->assertCount(13, $plain->headings());
        $this->assertCount(13, $plain->map($marked->fresh()));

        $extended = new CommittedShiftChangesExcelExport(clone $query, 'de', true);
        $this->assertSame(['Kurzfristig', 'Grund'], array_slice($extended->headings(), 13));
        $row = $extended->map($marked->fresh());
        $this->assertSame('Ja', $row[13]);
        $this->assertSame('Betriebsstörung (Stromausfall)', $row[14]);
    }

    #[Test]
    public function export_route_filters_and_extends_when_enabled(): void
    {
        Excel::fake();
        $this->setMarking(true);
        $this->actingAsAdmin();
        $shift = $this->shiftOn('2026-03-11');
        Carbon::setTestNow('2026-03-09 10:00:00');
        $regular = $this->recordChange($shift);
        Carbon::setTestNow('2026-03-10 17:00:00');
        $marked = $this->recordChange($shift);

        $this->get(route('committed-shift-changes.export', [
            'short_notice' => 1,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]))->assertOk();

        Excel::assertDownloaded(
            'aenderungen_2026-03-01_bis_2026-03-31.xlsx',
            function (CommittedShiftChangesExcelExport $export) use ($marked, $regular): bool {
                $ids = $export->query()->pluck('id')->all();

                return in_array($marked->id, $ids, true)
                    && ! in_array($regular->id, $ids, true)
                    && count($export->headings()) === 15;
            }
        );
    }

    #[Test]
    public function settings_can_be_updated(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('shift.settings.update.short-notice-marking'), [
            'short_notice_marking_enabled' => true,
            'short_notice_cutoff_time' => '12:30',
        ])->assertRedirect();

        $settings = app(ShiftSettings::class)->refresh();
        $this->assertTrue($settings->short_notice_marking_enabled);
        $this->assertSame('12:30', $settings->short_notice_cutoff_time);

        $this->patch(route('shift.settings.update.short-notice-marking'), [
            'short_notice_marking_enabled' => true,
            'short_notice_cutoff_time' => '25:00',
        ])->assertSessionHasErrors('short_notice_cutoff_time');
    }
}
