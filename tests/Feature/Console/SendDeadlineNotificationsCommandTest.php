<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
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
}
