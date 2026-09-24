<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * artwork:purge-trash leert den Papierkorb nach 30 Tagen über dieselben Wege wie "Endgültig löschen".
 */
final class PurgeTrashCommandTest extends FeatureTestCase
{
    private function trashedDaysAgo(object $model, int $days): void
    {
        $model->delete();
        DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()->subDays($days)]);
    }

    #[Test]
    public function only_entries_older_than_thirty_days_are_deleted_permanently(): void
    {
        $oldShift = Shift::factory()->create(['event_id' => null, 'room_id' => Room::factory()->create()->id]);
        $recentShift = Shift::factory()->create(['event_id' => null, 'room_id' => Room::factory()->create()->id]);
        $oldGenre = Genre::factory()->create();
        $oldEvent = Event::factory()->create(['project_id' => null]);
        $this->trashedDaysAgo($oldShift, 31);
        $this->trashedDaysAgo($recentShift, 5);
        $this->trashedDaysAgo($oldGenre, 40);
        $this->trashedDaysAgo($oldEvent, 31);

        $this->artisan('artwork:purge-trash')->assertSuccessful();

        $this->assertDatabaseMissing('shifts', ['id' => $oldShift->id]);
        $this->assertSoftDeleted('shifts', ['id' => $recentShift->id]);
        $this->assertDatabaseMissing('genres', ['id' => $oldGenre->id]);
        $this->assertDatabaseMissing('events', ['id' => $oldEvent->id]);
    }

    #[Test]
    public function projects_are_deleted_through_the_force_delete_job(): void
    {
        Bus::fake([ForceDeleteProjectJob::class]);
        $project = Project::factory()->create();
        $this->trashedDaysAgo($project, 31);

        $this->artisan('artwork:purge-trash')->expectsOutputToContain('Projekte: 1 gelöscht')->assertSuccessful();

        Bus::assertDispatched(
            ForceDeleteProjectJob::class,
            static fn (ForceDeleteProjectJob $job): bool => (fn () => $this->projectId)->call($job) === $project->id
        );
    }

    #[Test]
    public function dry_run_deletes_nothing(): void
    {
        $shift = Shift::factory()->create(['event_id' => null, 'room_id' => Room::factory()->create()->id]);
        $this->trashedDaysAgo($shift, 31);

        $this->artisan('artwork:purge-trash', ['--dry-run' => true])
            ->expectsOutputToContain('Schichten: 1 würden gelöscht')
            ->assertSuccessful();

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function the_command_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('artwork:purge-trash')->assertSuccessful();
    }
}
