<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * Fristerinnerung für Aufgaben: zugewiesene Personen werden erinnert – auch wenn ihnen die (geteilte)
 * Checkliste gehört – und die Erinnerung führt per „In Aufgaben ansehen“ zur Aufgabe.
 */
final class SendDeadlineNotificationsCommandTest extends FeatureTestCase
{
    #[Test]
    public function the_checklist_owner_is_reminded_of_a_task_assigned_to_them(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $owner = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($owner);
        $checklist = Checklist::factory()->create(['user_id' => $owner->id, 'private' => false]);
        $task = Task::factory()->create([
            'checklist_id' => $checklist->id,
            'deadline' => now()->subHour(),
            'done_at' => null,
            'sent_deadline_notification' => false,
        ]);
        $task->task_users()->attach($owner->id);

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $data = $owner->notifications()->sole()->data;
        $this->assertSame(NotificationEnum::NOTIFICATION_TASK_REMINDER->value, $data['type']);
        $this->assertSame(['showInTasks'], $data['buttons']);
        $this->assertSame($task->id, $data['taskId']);
    }

    #[Test]
    public function a_newly_created_task_is_reminded_once_its_deadline_has_passed(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $owner = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($owner);
        $checklist = Checklist::factory()->create(['user_id' => $owner->id, 'private' => false]);
        $task = Task::query()->create([
            'name' => 'Programmheft freigeben',
            'checklist_id' => $checklist->id,
            'deadline' => now()->subHour(),
            'done' => false,
            'order' => 1,
        ]);
        $task->task_users()->attach($owner->id);

        $this->assertFalse($task->fresh()->sent_deadline_notification);

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $this->assertSame(
            NotificationEnum::NOTIFICATION_TASK_REMINDER->value,
            $owner->notifications()->sole()->data['type']
        );
        $this->assertTrue($task->fresh()->sent_deadline_notification);
    }

    #[Test]
    public function a_failing_reminder_does_not_abort_the_run_or_repeat_daily(): void
    {
        $this->partialMock(NotificationService::class, function ($mock): void {
            $mock->shouldReceive('createNotification')->andThrow(new RuntimeException('mail down'));
        });
        $owner = User::factory()->create();
        $checklist = Checklist::factory()->create(['user_id' => $owner->id, 'private' => false]);
        $tasks = Task::factory()->count(2)->create([
            'checklist_id' => $checklist->id,
            'deadline' => now()->subHour(),
            'done_at' => null,
            'sent_deadline_notification' => false,
        ]);
        $tasks->each(fn (Task $task) => $task->task_users()->attach($owner->id));

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $tasks->each(fn (Task $task) => $this->assertTrue((bool) $task->fresh()->sent_deadline_notification));
    }
}
