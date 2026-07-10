<?php

namespace Tests\Feature\Modules\Worker;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Worker\Services\WorkerService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Stellt sicher, dass das shifts-Select in WorkerEagerLoadConfig alle Spalten
 * lädt, die WorkerShiftPlanResource ausgibt — insbesondere in_workflow,
 * das das "Angefragt"-Badge im Schichtplan steuert.
 */
final class WorkerShiftPlanPayloadTest extends FeatureTestCase
{
    private const DAY = '2026-08-12';

    #[Test]
    public function bulk_shift_plan_payload_contains_in_workflow_flag(): void
    {
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $shift = Shift::factory()->create([
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'event_start_day' => self::DAY,
            'event_end_day' => self::DAY,
            'start' => '10:00',
            'end' => '14:00',
            'is_committed' => false,
            'in_workflow' => true,
        ]);

        $qualification = ShiftQualification::factory()->create();
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $workerService = app(WorkerService::class);
        $startDate = Carbon::parse(self::DAY)->startOfWeek();
        $endDate = Carbon::parse(self::DAY)->endOfWeek();

        $workers = $workerService->getWorkersForShiftPlanByIds(
            User::class,
            [$worker->id],
            $startDate,
            $endDate
        );

        $loadedShift = $workers->sole()->shifts->sole();
        $this->assertTrue(
            (bool) $loadedShift->in_workflow,
            'in_workflow fehlt im shifts-Select von WorkerEagerLoadConfig.'
        );

        $request = Request::create('/shift-plan');
        $request->setUserResolver(static fn (): ?User => null);

        $payload = (new UserShiftPlanResource($workers->sole()))
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setQualificationsCache($workerService->buildQualificationsCache($workers))
            ->toArray($request);

        $shiftPayload = collect($payload['shifts'])->sole();
        $this->assertTrue((bool) $shiftPayload['inWorkflow']);
        $this->assertFalse((bool) $shiftPayload['isCommitted']);
    }

    #[Test]
    public function eager_load_select_covers_all_shift_columns_used_by_resource(): void
    {
        $worker = User::factory()->create(['can_work_shifts' => true]);

        $shift = Shift::factory()->create([
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'event_start_day' => self::DAY,
            'event_end_day' => self::DAY,
            'start' => '10:00',
            'end' => '14:00',
        ]);

        $qualification = ShiftQualification::factory()->create();
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $workers = app(WorkerService::class)->getWorkersForShiftPlanByIds(
            User::class,
            [$worker->id],
            Carbon::parse(self::DAY)->startOfWeek(),
            Carbon::parse(self::DAY)->endOfWeek()
        );

        $loadedShift = $workers->sole()->shifts->sole();

        // Alle Shift-Spalten, die WorkerShiftPlanResource direkt ausliest —
        // fehlt eine im Select, ist sie im Bulk-Payload stumm null.
        $requiredColumns = [
            'id',
            'start_date',
            'end_date',
            'start',
            'end',
            'description',
            'is_committed',
            'in_workflow',
            'event_start_day',
            'event_end_day',
            'craft_id',
            'room_id',
            'event_id',
            'shift_group_id',
        ];

        $loadedAttributes = array_keys($loadedShift->getAttributes());
        foreach ($requiredColumns as $column) {
            $this->assertContains(
                $column,
                $loadedAttributes,
                "Spalte '{$column}' wird von WorkerShiftPlanResource verwendet, "
                    . 'fehlt aber im shifts-Select von WorkerEagerLoadConfig.'
            );
        }
    }
}
