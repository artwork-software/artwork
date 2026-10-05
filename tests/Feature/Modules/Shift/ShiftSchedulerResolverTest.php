<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Support\ShiftSchedulerResolver;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Konflikthinweis in Verfügbarkeiten/Abwesenheiten darf nur dann jemanden als
 * einteilende Person nennen, wenn die Zuweisung diese Person auch gespeichert hat.
 * Fehlt der Urheber (Zuweisungen von vor assigned_by_user_id), liefert der Resolver
 * den Festschreibenden — aber mit der Quelle "committed", damit der Text nicht
 * behauptet, diese Person habe eingeplant.
 */
final class ShiftSchedulerResolverTest extends FeatureTestCase
{
    /**
     * @return array{0: Shift, 1: User, 2: User}
     */
    private function createCommittedShiftWithUser(): array
    {
        $committer = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => true,
            'committing_user_id' => $committer->id,
        ]);

        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        return [$shift->fresh(), $user, $committer];
    }

    #[Test]
    public function it_names_the_assigner_when_the_assignment_recorded_one(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();
        $assigner = User::factory()->create(['first_name' => 'Dennis', 'last_name' => 'Döscher']);

        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $user->id)
            ->update(['assigned_by_user_id' => $assigner->id, 'created_at' => '2026-06-05 11:11:00']);

        $scheduler = ShiftSchedulerResolver::resolve($shift, $user);

        $this->assertSame('Dennis Döscher', $scheduler['name']);
        $this->assertSame(ShiftSchedulerResolver::SOURCE_ASSIGNED, $scheduler['source']);
        $this->assertSame('2026-06-05 11:11:00', $scheduler['at']);
    }

    #[Test]
    public function it_flags_the_committer_as_such_when_no_assigner_was_recorded(): void
    {
        [$shift, $user, $committer] = $this->createCommittedShiftWithUser();

        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $user->id)
            ->update(['assigned_by_user_id' => null, 'created_at' => '2026-06-05 11:11:00']);

        $scheduler = ShiftSchedulerResolver::resolve($shift, $user);

        $this->assertSame($committer->full_name, $scheduler['name']);
        $this->assertSame(ShiftSchedulerResolver::SOURCE_COMMITTED, $scheduler['source']);
        // Der Zuweisungszeitpunkt bleibt bekannt, auch wenn der Urheber fehlt
        $this->assertSame('2026-06-05 11:11:00', $scheduler['at']);
    }

    #[Test]
    public function it_returns_no_source_when_neither_assigner_nor_committer_exist(): void
    {
        [$shift, $user] = $this->createCommittedShiftWithUser();
        $shift->update(['committing_user_id' => null]);

        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $user->id)
            ->update(['assigned_by_user_id' => null]);

        $scheduler = ShiftSchedulerResolver::resolve($shift->fresh(), $user);

        $this->assertNull($scheduler['name']);
        $this->assertNull($scheduler['source']);
    }
}
