<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\WeeklyPlanPdfBuilder;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wochenplan als PDF (je Person eine Kalenderwoche plus unverbindliche Vorschau auf die Folgewoche):
 * Inhalt, Wochengrenzen, Dienste über Mitternacht, Rechte und Sammel-PDF.
 * Testwoche: Mo 13.07.2026 bis So 19.07.2026, Vorschau 20.07. bis 26.07.2026.
 */
final class WeeklyPlanPdfExportControllerTest extends FeatureTestCase
{
    private function worker(string $firstName = 'Anna', string $lastName = 'Muster'): User
    {
        return User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'can_work_shifts' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $shiftAttributes
     */
    private function assignShift(
        User $worker,
        string $date,
        string $start,
        string $end,
        array $shiftAttributes = [],
        ?string $endDate = null
    ): Shift {
        $endDate ??= $date;
        $shift = Shift::factory()->create(array_merge([
            'event_id' => null,
            'start_date' => $date,
            'end_date' => $endDate,
            'event_start_day' => $date,
            'event_end_day' => $endDate,
            'start' => $start,
            'end' => $end,
        ], $shiftAttributes));
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create(['name' => 'Leitung'])->id,
            'start_date' => $date,
            'end_date' => $endDate,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return $shift;
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function day(array $page, string $date, string $block = 'days'): array
    {
        $day = collect($page[$block])->firstWhere('date', $date);
        $this->assertNotNull($day, "Tag {$date} fehlt im Block {$block}");

        return $day;
    }

    private function mockPdf(\Closure $assertData, string $paperSize = 'a4', string $orientation = 'landscape'): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('pdf.weekly_plan', Mockery::on(function (array $data) use ($assertData): bool {
                $assertData($data);

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->with($paperSize, $orientation)->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->with('dpi', 96)->andReturnSelf();
        $pdf->shouldReceive('save')->once();
        $this->app->instance(PdfWrapper::class, $pdf);
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $worker = $this->worker();

        $this->post(route('user.shiftplan.export.weekly-pdf', $worker->id), ['week' => '2026-07-15'])
            ->assertRedirect(route('login'));
        $this->post(route('shift.plan.export.weekly-plan.pdf'), ['week' => '2026-07-15'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function page_contains_shifts_free_days_absences_and_the_preview(): void
    {
        $worker = $this->worker();
        $room = Room::factory()->create(['name' => 'Studio 1']);
        $craft = Craft::factory()->create(['name' => 'Beleuchtung']);

        $this->assignShift($worker, '2026-07-15', '09:15', '16:45', [
            'room_id' => $room->id,
            'craft_id' => $craft->id,
            'is_committed' => true,
        ]);
        $this->assignShift($worker, '2026-07-16', '10:00', '14:00');
        // Folgewoche: nur in der Vorschau
        $this->assignShift($worker, '2026-07-21', '08:00', '12:00');
        // Außerhalb beider Wochen
        $this->assignShift($worker, '2026-07-28', '08:00', '12:00');
        $this->assignShift($worker, '2026-07-10', '08:00', '12:00');

        $worker->individualTimes()->create([
            'title' => 'Besprechung',
            'start_date' => '2026-07-14',
            'end_date' => '2026-07-14',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'full_day' => false,
        ]);

        // Geplanter halber freier Tag (Tagesstatus) und gewährter ganzer Ersatzfreitag
        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-07-17',
            'full_day' => false,
            'day_part' => 'morning',
            'type' => 'FREE_WORK',
        ]);
        CompensationDayOff::query()->create([
            'user_id' => $worker->id,
            'value' => 1.0,
            'deadline' => '2026-08-31',
            'granted_date' => '2026-07-18',
            'granted_at' => now(),
        ]);
        // Abwesenheit mit Kommentar: im Plan steht nur "abwesend"
        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-07-13',
            'full_day' => true,
            'type' => 'OFF_WORK',
            'comment' => 'Arzttermin',
        ]);
        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-07-22',
            'full_day' => true,
            'type' => 'NOT_AVAILABLE',
        ]);

        // Ein Mittwoch als Eingabe: die Woche beginnt am Montag davor
        $page = app(WeeklyPlanPdfBuilder::class)->buildPage($worker, Carbon::parse('2026-07-15'));

        $this->assertSame('Anna Muster', $page['name']);
        $this->assertSame('2026-07-13', $page['weekStart']);
        $this->assertCount(7, $page['days']);
        $this->assertCount(7, $page['previewDays']);
        $this->assertSame('2026-07-13', $page['days'][0]['date']);
        $this->assertSame('2026-07-19', $page['days'][6]['date']);
        $this->assertSame('2026-07-20', $page['previewDays'][0]['date']);
        $this->assertSame('2026-07-26', $page['previewDays'][6]['date']);
        $this->assertTrue($page['hasEntries']);
        $this->assertTrue($page['hasPreviewEntries']);

        $wednesday = $this->day($page, '2026-07-15');
        $this->assertCount(1, $wednesday['shifts']);
        $shift = $wednesday['shifts'][0];
        $this->assertSame('09:15', $shift['start']);
        $this->assertSame('16:45', $shift['end']);
        $this->assertFalse($shift['endsNextDay']);
        $this->assertSame('Studio 1', $shift['room']);
        $this->assertSame('Beleuchtung', $shift['craft']);
        $this->assertSame('Leitung', $shift['function']);
        $this->assertTrue($shift['committed']);

        $this->assertFalse($this->day($page, '2026-07-16')['shifts'][0]['committed']);
        $this->assertSame('Besprechung', $this->day($page, '2026-07-14')['individualTimes'][0]['title']);

        $this->assertSame(
            [['half' => true, 'period' => 'morning', 'compensation' => false]],
            $this->day($page, '2026-07-17')['freeDays']
        );
        $this->assertSame(
            [['half' => false, 'period' => null, 'compensation' => true]],
            $this->day($page, '2026-07-18')['freeDays']
        );

        $absence = $this->day($page, '2026-07-13')['absences'];
        $this->assertSame(
            [['unavailable' => false, 'full_day' => true, 'start' => null, 'end' => null]],
            $absence
        );
        $this->assertStringNotContainsString('Arzttermin', json_encode($page));

        $this->assertCount(1, $this->day($page, '2026-07-21', 'previewDays')['shifts']);
        $this->assertTrue($this->day($page, '2026-07-22', 'previewDays')['absences'][0]['unavailable']);

        $allShiftDates = collect([...$page['days'], ...$page['previewDays']])
            ->filter(fn (array $day): bool => $day['shifts'] !== [])
            ->pluck('date')
            ->all();
        $this->assertSame(['2026-07-15', '2026-07-16', '2026-07-21'], $allShiftDates);
    }

    #[Test]
    public function overnight_shift_on_sunday_stays_in_its_week(): void
    {
        $worker = $this->worker();
        $this->assignShift($worker, '2026-07-19', '22:00', '06:00', [], '2026-07-20');

        $page = app(WeeklyPlanPdfBuilder::class)->buildPage($worker, Carbon::parse('2026-07-13'));

        $sunday = $this->day($page, '2026-07-19');
        $this->assertCount(1, $sunday['shifts']);
        $this->assertSame('22:00', $sunday['shifts'][0]['start']);
        $this->assertSame('06:00', $sunday['shifts'][0]['end']);
        $this->assertTrue($sunday['shifts'][0]['endsNextDay']);
        $this->assertSame([], $this->day($page, '2026-07-20', 'previewDays')['shifts']);
    }

    #[Test]
    public function week_without_entries_is_reported_as_empty(): void
    {
        $page = app(WeeklyPlanPdfBuilder::class)->buildPage($this->worker(), Carbon::parse('2026-07-13'));

        $this->assertFalse($page['hasEntries']);
        $this->assertFalse($page['hasPreviewEntries']);
    }

    #[Test]
    public function own_weekly_plan_needs_the_own_roster_permission(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $this->assignShift($user, '2026-07-15', '09:00', '17:00');
        $this->mockPdf(function (array $data) use ($user): void {
            $this->assertCount(1, $data['pages']);
            $this->assertSame(trim($user->first_name . ' ' . $user->last_name), $data['pages'][0]['name']);
            $this->assertSame($user->full_name, $data['createdBy']);
            $this->assertCount(1, collect($data['pages'][0]['days'])->firstWhere('date', '2026-07-15')['shifts']);
        });

        $this->post(route('user.shiftplan.export.weekly-pdf', $user->id), ['week' => '2026-07-15'])
            ->assertRedirect();
    }

    #[Test]
    public function own_weekly_plan_is_forbidden_without_the_own_roster_permission(): void
    {
        $user = $this->actingAsUserWith([]);

        $this->post(route('user.shiftplan.export.weekly-pdf', $user->id), ['week' => '2026-07-15'])
            ->assertForbidden();
    }

    #[Test]
    public function foreign_weekly_plan_is_forbidden_with_only_the_own_roster_permission(): void
    {
        $other = $this->worker('Ben', 'Beispiel');
        $this->actingAsUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $this->post(route('user.shiftplan.export.weekly-pdf', $other->id), ['week' => '2026-07-15'])
            ->assertForbidden();
    }

    #[Test]
    public function foreign_weekly_plan_needs_roster_view_permissions(): void
    {
        $other = $this->worker('Ben', 'Beispiel');

        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $this->mockPdf(function (array $data): void {
            $this->assertSame('Ben Beispiel', $data['pages'][0]['name']);
        });
        $this->post(route('user.shiftplan.export.weekly-pdf', $other->id), ['week' => '2026-07-15'])
            ->assertRedirect();
    }

    #[Test]
    public function single_export_validates_the_week(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $this->post(route('user.shiftplan.export.weekly-pdf', $user->id), ['week' => '15.07.2026'])
            ->assertSessionHasErrors('week');
    }

    #[Test]
    public function bulk_export_needs_the_shift_plan_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $this->post(route('shift.plan.export.weekly-plan.pdf'), ['week' => '2026-07-15'])
            ->assertForbidden();
    }

    #[Test]
    public function bulk_export_creates_one_page_per_person_with_entries(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $busy = $this->worker('Zoe', 'Aktiv');
        $this->assignShift($busy, '2026-07-15', '09:00', '17:00');
        $idle = $this->worker('Ole', 'Leer');

        $this->mockPdf(function (array $data): void {
            $names = collect($data['pages'])->pluck('name')->all();
            $this->assertContains('Zoe Aktiv', $names);
            $this->assertNotContains('Ole Leer', $names);
        }, 'a4', 'portrait');

        $this->post(route('shift.plan.export.weekly-plan.pdf'), [
            'week' => '2026-07-15',
            'worker_types' => ['user'],
            'paperOrientation' => 'portrait',
        ])->assertRedirect();
    }

    #[Test]
    public function bulk_export_can_include_people_without_entries_and_filters_by_craft(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $craft = Craft::factory()->create();
        $inCraft = $this->worker('Ida', 'Gewerk');
        $inCraft->assignedCrafts()->attach($craft->id);
        $this->worker('Udo', 'Anders');

        $this->mockPdf(function (array $data): void {
            $this->assertSame(['Ida Gewerk'], collect($data['pages'])->pluck('name')->all());
        });

        $this->post(route('shift.plan.export.weekly-plan.pdf'), [
            'start' => '2026-07-15',
            'worker_types' => ['user'],
            'craft_ids' => [$craft->id],
            'include_empty_workers' => true,
        ])->assertRedirect();
    }

    #[Test]
    public function bulk_export_for_explicit_people_keeps_empty_pages_sorted_by_last_name(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $first = $this->worker('Zoe', 'Alpha');
        $second = $this->worker('Adam', 'Zeta');
        $this->worker('Nicht', 'Gewählt');

        $this->mockPdf(function (array $data): void {
            $this->assertSame(['Zoe Alpha', 'Adam Zeta'], collect($data['pages'])->pluck('name')->all());
        });

        $this->post(route('shift.plan.export.weekly-plan.pdf'), [
            'week' => '2026-07-15',
            'workers' => [
                ['type' => 'user', 'id' => $second->id],
                ['type' => 'user', 'id' => $first->id],
            ],
        ])->assertRedirect();
    }

    #[Test]
    public function the_view_renders_all_blocks(): void
    {
        $worker = $this->worker();
        $this->assignShift($worker, '2026-07-19', '22:00', '06:00', ['is_committed' => true], '2026-07-20');
        $this->assignShift($worker, '2026-07-16', '10:00', '14:00');
        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-07-17',
            'full_day' => false,
            'day_part' => 'afternoon',
            'type' => 'FREE_WORK',
        ]);
        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-07-13',
            'full_day' => true,
            'type' => 'OFF_WORK',
        ]);
        app()->setLocale('de');

        $html = view('pdf.weekly_plan', [
            'pages' => [app(WeeklyPlanPdfBuilder::class)->buildPage($worker, Carbon::parse('2026-07-13'))],
            'title' => 'Wochenplan',
            'createdBy' => 'Test Person',
            'createdAt' => '27.11.2026 10:00',
            'bigLogoBase64' => null,
        ])->render();

        $this->assertStringContainsString('Wochenplan: Anna Muster', $html);
        $this->assertStringContainsString('KW 29/2026', $html);
        $this->assertStringContainsString('22:00–06:00 (+1)', $html);
        $this->assertStringContainsString('noch nicht verbindlich', $html);
        $this->assertStringContainsString('Halber freier Tag (Nachmittag)', $html);
        $this->assertStringContainsString('Abwesend', $html);
        $this->assertStringContainsString('Vorschau auf die Folgewoche', $html);
        $this->assertStringContainsString('unverbindlich, Änderungen vorbehalten', $html);
        $this->assertStringContainsString('Stand: 27.11.2026 10:00', $html);
    }
}
