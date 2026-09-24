<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projektmodus im Kalender verweist auf ein gelöschtes Projekt: Kalender und
 * Planungskalender dürfen nicht mit „Projekt nicht gefunden" (404) sperren,
 * sondern setzen den Projektmodus zurück.
 */
final class CalendarDeletedTimePeriodProjectTest extends FeatureTestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function calendarRoutes(): array
    {
        return [
            'kalender, projektmodus aktiv' => ['events', true],
            'kalender, projektmodus aus, id veraltet' => ['events', false],
            'planungskalender, projektmodus aktiv' => ['planning-event-calendar.index', true],
            'planungskalender, projektmodus aus, id veraltet' => ['planning-event-calendar.index', false],
        ];
    }

    #[Test]
    #[DataProvider('calendarRoutes')]
    public function calendar_opens_and_resets_project_mode_when_project_was_deleted(
        string $routeName,
        bool $useProjectTimePeriod
    ): void {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();

        $admin->calendar_settings()->updateOrCreate([], [
            'use_project_time_period' => $useProjectTimePeriod,
            'time_period_project_id' => $project->id,
        ]);

        // Löschen ohne Model-Events (Altbestand vor dem Cleanup-Listener)
        DB::table('projects')->where('id', $project->id)->update(['deleted_at' => now()]);
        $admin->unsetRelation('calendar_settings');

        $this->get(route($routeName))->assertOk();

        $settings = $admin->calendar_settings()->first();
        $this->assertFalse($settings->use_project_time_period);
        $this->assertSame(0, (int) $settings->time_period_project_id);
    }

    #[Test]
    public function calendar_opens_when_project_was_permanently_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();

        $admin->calendar_settings()->updateOrCreate([], [
            'use_project_time_period' => true,
            'time_period_project_id' => $project->id,
        ]);

        DB::table('projects')->where('id', $project->id)->delete();
        $admin->unsetRelation('calendar_settings');

        $this->get(route('events'))->assertOk();

        $settings = $admin->calendar_settings()->first();
        $this->assertFalse($settings->use_project_time_period);
        $this->assertSame(0, (int) $settings->time_period_project_id);
    }

    #[Test]
    public function calendar_keeps_project_mode_for_existing_project(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();

        $admin->calendar_settings()->updateOrCreate([], [
            'use_project_time_period' => true,
            'time_period_project_id' => $project->id,
        ]);
        $admin->unsetRelation('calendar_settings');

        $this->get(route('events'))->assertOk();

        $settings = $admin->calendar_settings()->first();
        $this->assertTrue($settings->use_project_time_period);
        $this->assertSame($project->id, (int) $settings->time_period_project_id);
    }

    #[Test]
    public function deleting_project_resets_project_mode_in_all_calendar_and_shift_plan_settings(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $relations = [
            'calendar_settings',
            'daily_view_calendar_settings',
            'shift_plan_settings',
            'shift_plan_daily_settings',
        ];

        foreach ($relations as $relation) {
            $user->{$relation}()->updateOrCreate([], [
                'use_project_time_period' => true,
                'time_period_project_id' => $project->id,
            ]);
        }

        $project->delete();

        foreach ($relations as $relation) {
            $settings = $user->{$relation}()->first();
            $this->assertFalse((bool) $settings->use_project_time_period, $relation);
            $this->assertSame(0, (int) $settings->time_period_project_id, $relation);
        }
    }
}
