<?php

namespace Tests\Feature\Modules\Task;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * „Meine Aufgaben“: welche Checklisten erscheinen und wie sie nach dem ersten Projekttermin sortiert werden.
 */
final class OwnTasksChecklistsTest extends FeatureTestCase
{
    #[Test]
    public function only_assigned_or_own_checklists_are_listed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);

        $assigned = $this->checklist($project, 'Zugewiesen');
        $assigned->users()->attach($user->id);
        $withTask = $this->checklist($project, 'Mit Aufgabe');
        Task::factory()->create(['checklist_id' => $withTask->id])->task_users()->attach($user->id);
        $this->checklist($project, 'Nur Projektmitglied');
        $private = $this->checklist(null, 'Privat', ['private' => true, 'user_id' => $user->id]);
        $this->checklist(null, 'Fremd privat', ['private' => true, 'user_id' => User::factory()->create()->id]);

        $props = $this->get(route('tasks.own'))->assertOk()->viewData('page')['props'];

        $this->assertEqualsCanonicalizing(
            [$assigned->id, $withTask->id],
            collect($props['public_checklists'])->pluck('id')->all()
        );
        $this->assertSame([$private->id], collect($props['private_checklists'])->pluck('id')->all());
    }

    #[Test]
    public function sorting_by_first_event_uses_one_event_query(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $expectedOrder = [];
        foreach (['2026-12-01', '2026-10-01', '2026-11-01'] as $index => $day) {
            $project = Project::factory()->create();
            $this->event($project, $day);
            $this->event($project, '2027-01-0' . ($index + 1));
            $checklist = $this->checklist($project, 'Projekt ' . $day);
            $checklist->users()->attach($user->id);
            $expectedOrder[$day] = $checklist->id;
        }
        $withoutProject = $this->checklist(null, 'Ohne Projekt');
        $withoutProject->users()->attach($user->id);
        ksort($expectedOrder);

        DB::enableQueryLog();
        $ascending = $this->get(route('tasks.own', ['filter' => 1]))->assertOk()->viewData('page')['props'];
        $eventQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from `events`'))
            ->count();
        DB::disableQueryLog();
        $descending = $this->get(route('tasks.own', ['filter' => 2]))->assertOk()->viewData('page')['props'];

        $this->assertSame(1, $eventQueries);
        $this->assertSame(
            [...array_values($expectedOrder), $withoutProject->id],
            collect($ascending['public_checklists'])->pluck('id')->all()
        );
        $this->assertSame(
            [$withoutProject->id, ...array_reverse(array_values($expectedOrder))],
            collect($descending['public_checklists'])->pluck('id')->all()
        );
    }

    private function event(Project $project, string $day): void
    {
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => $day . ' 10:00:00',
            'end_time' => $day . ' 12:00:00',
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function checklist(?Project $project, string $name, array $attributes = []): Checklist
    {
        return Checklist::factory()->create(array_merge([
            'name' => $name,
            'project_id' => $project?->id,
            'private' => false,
        ], $attributes));
    }
}
