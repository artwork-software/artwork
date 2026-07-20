<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\WorkerShiftPlanPdfBuilder;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class WorkerShiftPlanPdfExportControllerTest extends FeatureTestCase
{
    #[Test]
    public function guestCannotExportTheWorkerMatrix(): void
    {
        $this->post(route('shift.plan.export.worker-matrix.pdf'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function userWithoutShiftPlanPermissionCannotExportTheWorkerMatrix(): void
    {
        $this->actingAsUserWith([]);

        $this->post(route('shift.plan.export.worker-matrix.pdf'), $this->validPayload())
            ->assertForbidden();
    }

    #[Test]
    public function exportPeriodIsLimitedToThirtyOneDays(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->post(route('shift.plan.export.worker-matrix.pdf'), [
            ...$this->validPayload(),
            'end' => '2026-08-16',
        ])->assertSessionHasErrors('end');
    }

    #[Test]
    public function itExportsWorkersWithPivotTimesRoomsIndividualTimesAndDayServices(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $worker = User::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Muster',
            'can_work_shifts' => true,
        ]);
        $room = Room::factory()->create(['name' => 'Studio 1']);
        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'event_start_day' => '2026-07-15',
            'event_end_day' => '2026-07-15',
            'start' => '08:00',
            'end' => '18:00',
            'is_committed' => true,
        ]);
        $qualification = ShiftQualification::factory()->create(['name' => 'Leitung']);
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start_time' => '09:15',
            'end_time' => '16:45',
        ]);
        $worker->individualTimes()->create([
            'title' => 'Besprechung',
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'full_day' => false,
        ]);
        $dayService = DayService::factory()->create(['name' => 'Kasse', 'hex_color' => '#dc2626']);
        $worker->dayServices()->attach($dayService->id, ['date' => '2026-07-15']);

        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('pdf.shiftplan_worker_matrix', Mockery::on(function (array $data) use ($viewer): bool {
                $this->assertSame($viewer->full_name, $data['createdBy']);
                $this->assertSame(1, $data['workerCount']);

                $workerRow = collect($data['pages'])->first()['workers'][0];
                $this->assertSame('Anna Muster', $workerRow['displayName']);
                $cell = $workerRow['cells']['2026-07-15'];

                $this->assertSame('09:15', $cell['shifts'][0]['start']);
                $this->assertSame('16:45', $cell['shifts'][0]['end']);
                $this->assertSame('Studio 1', $cell['shifts'][0]['room']);
                $this->assertSame('Leitung', $cell['shifts'][0]['function']);
                $this->assertSame('Besprechung', $cell['individualTimes'][0]['title']);
                $this->assertSame('Kasse', $cell['dayServices'][0]['name']);

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->with('a3', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->with('dpi', 96)->andReturnSelf();
        $pdf->shouldReceive('save')->once();
        $this->app->instance(PdfWrapper::class, $pdf);

        $this->post(route('shift.plan.export.worker-matrix.pdf'), $this->validPayload())
            ->assertRedirect();
    }

    #[Test]
    public function itSplitsOvernightPivotTimesAcrossDaysWithoutDuplicatingWorkers(): void
    {
        $worker = User::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'Beispiel',
            'can_work_shifts' => true,
        ]);
        $crafts = Craft::factory()->count(2)->create();
        $worker->assignedCrafts()->attach($crafts->pluck('id'));
        $shift = Shift::factory()->create([
            'event_id' => null,
            'craft_id' => $crafts->first()->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-16',
            'event_start_day' => '2026-07-15',
            'event_end_day' => '2026-07-16',
            'start' => '20:00',
            'end' => '08:00',
        ]);
        $qualification = ShiftQualification::factory()->create();
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-16',
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $matrix = app(WorkerShiftPlanPdfBuilder::class)->build(
            Carbon::parse('2026-07-15'),
            Carbon::parse('2026-07-16')->endOfDay(),
            $this->validPayload(),
        );

        $this->assertSame(1, $matrix['workerCount']);
        $row = $matrix['pages'][0]['workers'][0];
        $this->assertSame('22:00', $row['cells']['2026-07-15']['shifts'][0]['start']);
        $this->assertSame('24:00', $row['cells']['2026-07-15']['shifts'][0]['end']);
        $this->assertSame('00:00', $row['cells']['2026-07-16']['shifts'][0]['start']);
        $this->assertSame('06:00', $row['cells']['2026-07-16']['shifts'][0]['end']);
    }

    #[Test]
    public function craft_filter_removes_other_shifts_without_hiding_the_worker(): void
    {
        $worker = User::factory()->create(['can_work_shifts' => true]);
        [$includedCraft, $excludedCraft] = Craft::factory()->count(2)->create()->all();
        $worker->assignedCrafts()->attach([$includedCraft->id, $excludedCraft->id]);
        $qualification = ShiftQualification::factory()->create();

        foreach ([$includedCraft, $excludedCraft] as $craft) {
            $shift = Shift::factory()->create([
                'event_id' => null,
                'craft_id' => $craft->id,
                'start_date' => '2026-07-15',
                'end_date' => '2026-07-15',
                'event_start_day' => '2026-07-15',
                'event_end_day' => '2026-07-15',
                'start' => '10:00',
                'end' => '12:00',
            ]);
            $worker->shifts()->attach($shift->id, [
                'shift_qualification_id' => $qualification->id,
                'start_date' => '2026-07-15',
                'end_date' => '2026-07-15',
                'start_time' => '10:00',
                'end_time' => '12:00',
            ]);
        }

        $matrix = app(WorkerShiftPlanPdfBuilder::class)->build(
            Carbon::parse('2026-07-15'),
            Carbon::parse('2026-07-16')->endOfDay(),
            [...$this->validPayload(), 'craft_ids' => [$includedCraft->id]],
        );

        $shifts = $matrix['pages'][0]['workers'][0]['cells']['2026-07-15']['shifts'];
        $this->assertCount(1, $shifts);
        $this->assertSame($includedCraft->abbreviation ?? $includedCraft->name, $shifts[0]['craft']);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'title' => 'Personalübersicht',
            'start' => '2026-07-15',
            'end' => '2026-07-16',
            'worker_types' => ['user'],
            'craft_ids' => [],
            'include_empty_workers' => false,
            'show_shifts' => true,
            'show_individual_times' => true,
            'show_day_services' => true,
            'paperSize' => 'a3',
            'paperOrientation' => 'landscape',
            'dpi' => 96,
        ];
    }
}
