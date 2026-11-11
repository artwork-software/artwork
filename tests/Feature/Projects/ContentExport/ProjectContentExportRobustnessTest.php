<?php

namespace Tests\Feature\Projects\ContentExport;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportConfiguration;
use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Grenzfälle des Projektinhalte-Exports aus dem Review vom 09.11.2026: Gruppen-Filter, leere Spaltenauswahl,
 * Zeitraumgrenzen, unvollständige Termine, Räume-Spalte, temporäre Dateien und der Projektkopf.
 */
final class ProjectContentExportRobustnessTest extends FeatureTestCase
{
    private const EXPORT = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;

    private User $member;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();
        $this->project = Project::factory()->create(['name' => 'Robust project']);
        $this->project->users()->attach($this->member->id);
    }

    #[Test]
    public function project_group_filter_accepts_only_existing_groups(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);
        $trashedGroup = Project::factory()->create(['is_group' => true]);
        $trashedGroup->delete();

        foreach ([$this->project->id, $trashedGroup->id] as $groupId) {
            $this->postJson(route('projects.content-export.preview'), [
                'filters' => ['project_group_id' => $groupId],
            ])->assertJsonValidationErrors('filters.project_group_id');
        }
    }

    #[Test]
    public function export_without_any_visible_column_is_rejected(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);
        $restricted = Component::create([
            'name' => 'Restricted',
            'type' => ProjectTabComponentEnum::TEXT_FIELD->value,
            'data' => [],
            'special' => false,
            'permission_type' => 'someSeeSomeEdit',
        ]);

        $this->postJson(route('projects.content-export.cache'), [
            'format' => 'xlsx',
            'columns' => [['key' => 'component:' . $restricted->id], ['key' => 'component:999999']],
        ])->assertJsonValidationErrors('columns');
    }

    #[Test]
    public function event_ending_exactly_at_the_start_of_the_range_does_not_count(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);
        $this->event('2026-02-28 22:00', '2026-03-01 00:00');

        $this->postJson(route('projects.content-export.preview'), [
            'filters' => ['date_start' => '2026-03-01', 'date_end' => '2026-03-31'],
        ])->assertOk()->assertJsonPath('count', 0);
    }

    #[Test]
    public function events_without_times_do_not_break_the_export(): void
    {
        $user = $this->actingAsUserWith(self::EXPORT, $this->member);
        $complete = $this->event('2026-03-02 19:00', '2026-03-02 21:00');
        $broken = $this->event('2026-03-03 19:00', '2026-03-03 21:00');
        Event::query()->whereKey($broken->id)->update(['end_time' => null]);

        $rows = $this->exportRows($user, [['key' => 'system:events']]);

        $this->assertSame([['Events'], ['Performance · Mon, 02.03.2026 · 19:00–21:00 · Hall']], $rows);
        $this->assertNotNull($complete->id);
    }

    #[Test]
    public function rooms_column_follows_the_event_types_of_the_event_column(): void
    {
        $user = $this->actingAsUserWith(self::EXPORT, $this->member);
        $this->event('2026-03-02 19:00', '2026-03-02 21:00');
        $setup = $this->event('2026-03-02 10:00', '2026-03-02 12:00', 'Setup', 'Workshop');

        $rows = $this->exportRows($user, [['key' => 'system:rooms']], [
            'event_column' => ['event_type_ids' => [$setup->event_type_id]],
        ]);

        $this->assertSame([['Rooms'], ['Workshop']], $rows);
    }

    #[Test]
    public function export_leaves_only_the_result_file_in_the_temp_directory(): void
    {
        $user = $this->actingAsUserWith(self::EXPORT, $this->member);
        $before = glob(sys_get_temp_dir() . '/project-content-*') ?: [];

        foreach (['xlsx', 'docx'] as $format) {
            $file = app(ProjectContentExportService::class)->export($user, ProjectContentExportConfiguration::fromArray([
                'format' => $format,
                'columns' => [['key' => 'system:project_name']],
            ]));
            $after = array_diff(glob(sys_get_temp_dir() . '/project-content-*') ?: [], $before);
            $this->assertSame([$file['path']], array_values($after), $format);
            unlink($file['path']);
        }
    }

    #[Test]
    public function export_entries_do_not_replace_the_latest_change_in_the_project_header(): void
    {
        $user = $this->actingAsUserWith(self::EXPORT, $this->member);
        // "Projekt angelegt" liegt vor der echten Änderung, der Export danach
        Activity::query()->where('subject_id', $this->project->id)
            ->where('subject_type', $this->project->getMorphClass())
            ->update(['created_at' => now()->subHours(2)]);
        Activity::query()->create([
            'log_name' => 'project',
            'description' => 'Real change',
            'subject_type' => $this->project->getMorphClass(),
            'subject_id' => $this->project->id,
            'event' => 'updated',
            'causer_type' => $user->getMorphClass(),
            'causer_id' => $user->id,
            'properties' => [['type' => 'project', 'translationKey' => 'Real change']],
            'created_at' => now()->subHour(),
        ]);
        $this->exportRows($user, [['key' => 'system:project_name']]);
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);

        $this->get(route('projects.tab', ['project' => $this->project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('headerObject.project_history.0.changes.0.translationKey', 'Real change'));
    }

    /**
     * @param array<int, array{key: string}> $columns
     * @param array<string, mixed> $overrides
     * @return array<int, array<int, string|null>>
     */
    private function exportRows(User $user, array $columns, array $overrides = []): array
    {
        $file = app(ProjectContentExportService::class)->export($user, ProjectContentExportConfiguration::fromArray([
            'format' => 'xlsx',
            'columns' => $columns,
            ...$overrides,
        ]));
        $rows = IOFactory::load($file['path'])->getActiveSheet()->toArray(null, false, false);
        unlink($file['path']);

        return $rows;
    }

    private function event(string $start, string $end, string $type = 'Performance', string $room = 'Hall'): Event
    {
        return Event::factory()->create([
            'project_id' => $this->project->id,
            'event_type_id' => EventType::query()->firstOrCreate(['name' => $type], EventType::factory()->raw(['name' => $type]))->id,
            'room_id' => Room::query()->firstOrCreate(['name' => $room], Room::factory()->raw(['name' => $room]))->id,
            'start_time' => Carbon::parse($start),
            'end_time' => Carbon::parse($end),
        ]);
    }
}
