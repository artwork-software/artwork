<?php

namespace Tests\Feature\Modules\Contract;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Folgeaufgaben an Verträgen: Zuständige werden über die Zuweisung benachrichtigt und vor sowie nach
 * der Frist erinnert, über dieselben Meldungsarten wie bei To-dos.
 */
final class ContractTaskNotificationsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::swap(new ChannelManager($this->app));
    }

    #[Test]
    public function assigned_users_are_notified_but_not_the_assigning_person(): void
    {
        $creator = $this->actingAsAdmin();
        $assignee = $this->userWithNotificationDefaults();
        app(NotificationSettingService::class)->ensureDefaultsForUser($creator);
        $contract = Contract::factory()->create(['creator_id' => $creator->id]);

        $this->patch(route('contracts.update', $contract), [
            'tasks' => [
                [
                    'new' => true,
                    'name' => 'Gegenzeichnung einholen',
                    'description' => '',
                    'deadline' => '2027-01-10 12:00',
                    'assigned_users' => [['id' => $assignee->id], ['id' => $creator->id]],
                ],
            ],
        ])->assertRedirect();

        $data = $assignee->notifications()->sole()->data;
        $this->assertSame(NotificationEnum::NOTIFICATION_NEW_TASK->value, $data['type']);
        $this->assertStringContainsString('Gegenzeichnung einholen', $data['title']);
        $link = collect($data['description'])->firstWhere('type', 'link');
        $this->assertSame(route('dashboard'), $link['href'], 'ohne Vertragszugriff führt der Link zum Dashboard');
        $this->assertSame(0, $creator->notifications()->count());
    }

    #[Test]
    public function an_assignee_who_may_open_the_contract_is_linked_to_its_project(): void
    {
        $this->actingAsAdmin();
        $assignee = $this->adminUser($this->userWithNotificationDefaults());
        $contract = Contract::factory()->create();

        $this->patch(route('contracts.update', $contract), [
            'tasks' => [
                [
                    'new' => true,
                    'name' => 'Rechnung prüfen',
                    'description' => '',
                    'deadline' => '2027-01-10 12:00',
                    'assigned_users' => [['id' => $assignee->id]],
                ],
            ],
        ])->assertRedirect();

        $link = collect($assignee->notifications()->sole()->data['description'])->firstWhere('type', 'link');
        $this->assertSame($contract->project->name, $link['title']);
        $this->assertStringContainsString('/projects/' . $contract->project_id . '/', $link['href']);
    }

    #[Test]
    public function the_reminder_before_the_deadline_is_sent_exactly_once(): void
    {
        $assignee = $this->userWithNotificationDefaults();
        $this->contractTaskFor($assignee, ['deadline' => now()->addHours(5)]);

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();
        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $data = $assignee->notifications()->sole()->data;
        $this->assertSame(NotificationEnum::NOTIFICATION_TASK_REMINDER->value, $data['type']);
        $this->assertSame([], $data['buttons'], 'Vertragsaufgaben stehen nicht in der Aufgabenübersicht');
    }

    #[Test]
    public function the_reminder_after_the_deadline_is_sent_exactly_once(): void
    {
        $assignee = $this->userWithNotificationDefaults();
        $task = $this->contractTaskFor($assignee, [
            'deadline' => now()->subHour(),
            'sent_deadline_tomorrow_notification' => true,
        ]);

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();
        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $this->assertSame(1, $assignee->notifications()->count());
        $this->assertTrue($task->fresh()->sent_deadline_notification);
    }

    #[Test]
    public function done_contract_tasks_are_not_reminded(): void
    {
        $assignee = $this->userWithNotificationDefaults();
        $this->contractTaskFor($assignee, ['deadline' => now()->subHour(), 'done' => true]);
        $this->contractTaskFor($assignee, ['deadline' => now()->addHours(5), 'done' => true]);

        $this->artisan('artwork:send-deadline-notifications')->assertSuccessful();

        $this->assertSame(0, $assignee->notifications()->count());
    }

    private function userWithNotificationDefaults(): User
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function contractTaskFor(User $assignee, array $attributes): Task
    {
        $task = Task::factory()->create(array_merge([
            'checklist_id' => null,
            'contract_id' => Contract::factory(),
            'done' => false,
            'sent_deadline_notification' => false,
            'sent_deadline_tomorrow_notification' => false,
        ], $attributes));
        $task->task_users()->attach($assignee->id);

        return $task;
    }
}
