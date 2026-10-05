<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Services\ProjectTeamNotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wer neu ins Projektteam kommt, wird benachrichtigt — egal über welchen Weg
 * (Projekt anlegen, Projektleitung setzen, Schicht, Aufgabe, Checkliste).
 */
final class ProjectTeamNotificationTest extends FeatureTestCase
{
    /**
     * Benachrichtigungen laufen per defer() nach der Response — bei direkten
     * Service-Aufrufen manuell ausführen.
     */
    private function flushDeferred(): void
    {
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();
    }

    private function assertNotifiedWith(User $user, string $translationKey, Project $project): void
    {
        $expectedTitle = __($translationKey, ['project' => $project->name], $user->refresh()->language);

        Notification::assertSentTo(
            $user,
            ProjectNotification::class,
            fn (ProjectNotification $notification) => $notification->toArray()->title === $expectedTitle
                && $notification->toArray()->projectId === $project->id
        );
    }

    #[Test]
    public function managers_selected_when_creating_a_project_are_notified_except_the_creator(): void
    {
        $creator = $this->actingAsAdmin();
        $manager = User::factory()->create();
        $project = Project::factory()->create();

        app(ProjectService::class)->attachManagementUsersWithoutSelf(
            $project,
            collect([$creator->id, $manager->id]),
            $creator->id
        );
        $this->flushDeferred();

        $this->assertNotifiedWith($manager, 'notification.project.leader.add', $project);
        Notification::assertNotSentTo($creator, ProjectNotification::class);
    }

    #[Test]
    public function only_newly_appointed_managers_are_notified_when_syncing_managers(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $existingManager = User::factory()->create();
        $teamMember = User::factory()->create();
        $newUser = User::factory()->create();

        $project->users()->attach($existingManager->id, ['is_manager' => true, 'can_write' => true]);
        $project->users()->attach($teamMember->id);

        app(ProjectService::class)->syncManagementUsers(
            $project,
            [$existingManager->id, $teamMember->id, $newUser->id]
        );
        $this->flushDeferred();

        $this->assertNotifiedWith($teamMember, 'notification.project.leader.add', $project);
        $this->assertNotifiedWith($newUser, 'notification.project.leader.add', $project);
        Notification::assertNotSentTo($existingManager, ProjectNotification::class);
    }

    #[Test]
    public function person_added_to_team_through_shift_assignment_is_notified_once(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $worker = User::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $service = app(ShiftWorkerService::class);

        foreach (['2026-06-08', '2026-06-09'] as $day) {
            $shift = Shift::factory()->create([
                'event_id' => Event::factory()->create(['project_id' => $project->id])->id,
                'event_start_day' => $day,
                'event_end_day' => $day,
                'start_date' => $day,
                'end_date' => $day,
                'start' => '10:00:00',
                'end' => '14:00:00',
            ]);
            $shift->shiftsQualifications()->create(['shift_qualification_id' => $qualification->id, 'value' => 1]);

            $service->assignToShift($shift, $worker, $qualification->id, 'TST', app(NotificationService::class));
        }
        $this->flushDeferred();

        $this->assertTrue($project->users()->where('users.id', $worker->id)->exists());
        $this->assertNotifiedWith($worker, 'notification.project.member.add', $project);
        Notification::assertSentToTimes($worker, ProjectNotification::class, 1);
    }

    #[Test]
    public function persons_added_to_team_through_tasks_and_checklists_are_notified(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        $project = $checklist->project;
        $taskUser = User::factory()->create();
        $checklistUser = User::factory()->create();

        app(TaskService::class)->createTaskByRequest($checklist, 'Aufgabe', $taskUser->id, null, null, [$taskUser->id]);
        app(ChecklistService::class)->assignUsersById($checklist->refresh(), [$checklistUser->id]);
        $this->flushDeferred();

        $this->assertNotifiedWith($taskUser, 'notification.project.member.add', $project);
        $this->assertNotifiedWith($checklistUser, 'notification.project.member.add', $project);
    }

    #[Test]
    public function no_second_notification_while_an_unread_one_for_the_same_project_exists(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $user = User::factory()->create();

        DatabaseNotification::query()->create([
            'id' => Str::uuid()->toString(),
            'type' => ProjectNotification::class,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => ['notificationKey' => sprintf('project-team-added-%d-%d', $project->id, $user->id)],
            'read_at' => null,
        ]);

        app(ProjectTeamNotificationService::class)->notifyAddedToTeam($project, [$user->id]);
        $this->flushDeferred();

        Notification::assertNotSentTo($user, ProjectNotification::class);
    }
}
