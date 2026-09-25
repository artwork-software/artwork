<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\TableService;
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

    #[Test]
    public function trashed_budget_templates_are_deleted_including_their_positions_and_cells(): void
    {
        $template = Table::factory()->create(['is_template' => true]);
        $column = Column::factory()->create(['table_id' => $template->id]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $template->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $cell = ColumnCell::factory()->create(['column_id' => $column->id, 'sub_position_row_id' => $row->id]);

        // wie "In den Papierkorb" in der Oberfläche: der ganze Baum wird soft-gelöscht
        app()->call([app(TableService::class), 'softDelete'], ['table' => $template]);
        DB::table('tables')->where('id', $template->id)->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('artwork:purge-trash')
            ->expectsOutputToContain('Budget-Vorlagen: 1 gelöscht')
            ->assertSuccessful();

        $this->assertDatabaseMissing('tables', ['id' => $template->id]);
        $this->assertDatabaseMissing('main_positions', ['id' => $mainPosition->id]);
        $this->assertDatabaseMissing('sub_positions', ['id' => $subPosition->id]);
        $this->assertDatabaseMissing('sub_position_rows', ['id' => $row->id]);
        $this->assertDatabaseMissing('column_sub_position_row', ['id' => $cell->id]);
        $this->assertDatabaseMissing('columns', ['id' => $column->id]);
    }
}
