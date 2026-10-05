<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Shift\Console\Commands\BackfillShiftWorkerAssignedByCommand;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\FeatureTestCase;

/**
 * Zuweisungen von vor shift_workers.assigned_by_user_id haben keinen Urheber —
 * der steht aber im Schichtverlauf. Ohne den Nachtrag nennt der Konflikthinweis
 * in Verfügbarkeiten/Abwesenheiten ersatzweise den Festschreibenden.
 */
final class BackfillShiftWorkerAssignedByCommandTest extends FeatureTestCase
{
    /**
     * Command isoliert ausführen — der volle Console-Kernel instanziiert jedes Command,
     * eines davon verträgt sich nicht mit Mail::fake().
     */
    private function runBackfill(): int
    {
        $command = $this->app->make(BackfillShiftWorkerAssignedByCommand::class);
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester->getStatusCode();
    }

    /**
     * @return array{0: Shift, 1: User, 2: User}
     */
    private function createAssignmentWithoutAssigner(): array
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start' => '09:00',
            'end' => '17:30',
            'is_committed' => true,
            'committing_user_id' => User::factory()->create()->id,
        ]);

        $worker = User::factory()->create(['first_name' => 'Malte', 'last_name' => 'Beispiel']);
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $worker->id)
            ->update(['assigned_by_user_id' => null, 'created_at' => '2026-06-05 11:11:00']);

        $assigner = User::factory()->create(['first_name' => 'Dennis', 'last_name' => 'Beispiel']);

        return [$shift->fresh(), $worker, $assigner];
    }

    private function logAssignment(Shift $shift, User $worker, User $assigner): void
    {
        activity('shift')
            ->performedOn($shift)
            ->causedBy($assigner)
            ->event('assigned')
            ->withProperties([
                'shift_id' => $shift->id,
                'translation_key_placeholder_values' => [$worker->full_name, 'Meister', 'LX', 'LX'],
            ])
            ->log('Worker assigned to shift');
    }

    #[Test]
    public function it_restores_the_assigner_from_the_shift_history(): void
    {
        [$shift, $worker, $assigner] = $this->createAssignmentWithoutAssigner();
        $this->logAssignment($shift, $worker, $assigner);

        $this->assertSame(0, $this->runBackfill());

        $this->assertSame(
            $assigner->id,
            (int) ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('employable_id', $worker->id)
                ->value('assigned_by_user_id')
        );
    }

    #[Test]
    public function it_moves_existing_conflict_hints_to_the_restored_assigner(): void
    {
        [$shift, $worker, $assigner] = $this->createAssignmentWithoutAssigner();
        $this->logAssignment($shift, $worker, $assigner);

        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-09-10',
            'full_day' => true,
        ]);

        $conflict = VacationConflict::query()->create([
            'vacation_id' => $vacation->id,
            'shift_id' => $shift->id,
            // Vor dem Fix stand hier der Name der festschreibenden Person
            'user_name' => 'Annika Beispiel',
            'date' => '2026-09-10',
            'start_time' => '09:00',
            'end_time' => '17:30',
        ]);

        $this->assertSame(0, $this->runBackfill());

        $conflict->refresh();

        $this->assertSame('Dennis Beispiel', $conflict->user_name);
        $this->assertSame('assigned', $conflict->scheduler_source);
        $this->assertNotNull($conflict->scheduled_at);
    }

    #[Test]
    public function it_leaves_already_recorded_assigners_untouched(): void
    {
        [$shift, $worker, $assigner] = $this->createAssignmentWithoutAssigner();
        $existing = User::factory()->create();

        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $worker->id)
            ->update(['assigned_by_user_id' => $existing->id]);

        $this->logAssignment($shift, $worker, $assigner);

        $this->assertSame(0, $this->runBackfill());

        $this->assertSame(
            $existing->id,
            (int) ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('employable_id', $worker->id)
                ->value('assigned_by_user_id')
        );
    }
}
