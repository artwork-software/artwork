<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppDashboardTest extends TestCase
{
    use CreatesUserShifts;

    #[Test]
    public function dashboardRequiresAuthentication(): void
    {
        $this->getJson(route('app.v1.dashboard'))->assertUnauthorized();
    }

    #[Test]
    public function dashboardReturnsEmptyBlocksForAUserWithoutData(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.dashboard'))
            ->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('today.date', now()->toDateString())
            ->assertJsonPath('today.shifts', [])
            ->assertJsonPath('events_today', [])
            ->assertJsonPath('tasks', [])
            ->assertJsonPath('unread_notifications', 0);
    }

    #[Test]
    public function dashboardReturnsTodaysShiftEventAndOpenTasks(): void
    {
        $user = User::factory()->create();

        $shift = $this->createShiftForUser($user, now());

        // An event today in a project the user works in.
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $projectEvent = Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => now()->setTime(10, 0),
            'end_time' => now()->setTime(12, 0),
        ]);

        // An open task on the user's checklist; done tasks stay hidden.
        $checklist = Checklist::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['checklist_id' => $checklist->id, 'done' => false]);
        Task::factory()->create(['checklist_id' => $checklist->id, 'done' => true]);

        Passport::actingAs($user, ['app']);

        $response = $this->getJson(route('app.v1.dashboard'))->assertOk();

        $this->assertSame($shift->id, $response->json('today.shifts.0.id'));
        $this->assertSame($projectEvent->id, $response->json('events_today.0.id'));
        $this->assertSame($project->name, $response->json('events_today.0.project.name'));
        $this->assertSame([$task->id], array_column($response->json('tasks'), 'id'));
    }

    #[Test]
    public function dashboardRejectsPassportTokensWithoutTheAppScope(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson(route('app.v1.dashboard'))->assertForbidden();
    }

    #[Test]
    public function appTokenCannotAccessTheMachineApi(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('api.v1.inventory.index'))->assertForbidden();
    }
}
