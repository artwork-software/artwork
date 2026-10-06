<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Core\Console\Commands\SendNotificationsEmailSummariesCommand;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Jobs\ArchiveUserNotificationsJob;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Notification\Support\NotificationMailPresenter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Rückstand und Größe der Sammelmail: E-Mail neu einschalten schickt keine Altmeldungen nach, je Gruppe
 * höchstens MAX_ENTRIES_PER_GROUP Einträge (Rest als „und X weitere“, trotzdem als zusammengefasst
 * markiert), artwork:update markiert nur bei fehlenden Einstellungen, „Alle archivieren“ erwischt alle.
 */
final class NotificationBacklogAndSummaryLimitTest extends FeatureTestCase
{
    private function userWithSettings(): User
    {
        $user = User::factory()->create(['language' => 'de']);
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeNotification(
        User $user,
        NotificationEnum $type,
        ?Carbon $createdAt = null,
        ?string $id = null,
        array $data = []
    ): string {
        $id ??= (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type->notificationClass() ?? 'test',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode($data + [
                'type' => $type->value,
                'groupType' => $type->groupType(),
                'title' => 'Meldung ' . $id,
                'description' => [],
                'buttons' => [],
            ]),
            'read_at' => null,
            'sent_in_summary' => false,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        return $id;
    }

    private function pendingCount(User $user, NotificationEnum $type): int
    {
        return $user->notifications()
            ->where('sent_in_summary', false)
            ->whereJsonContains('data->type', $type->value)
            ->count();
    }

    private function disableEmail(User $user, NotificationEnum $type): void
    {
        $user->notificationSettings()->where('type', $type->value)->update([
            'enabled_email' => false,
            'email_disabled_at' => now(),
        ]);
    }

    #[Test]
    public function switching_email_on_does_not_mail_the_backlog_from_the_time_without_email(): void
    {
        $this->freezeTime();
        $user = $this->userWithSettings();
        $this->actingAs($user);
        $switchedOff = [
            NotificationEnum::NOTIFICATION_TEAM,
            NotificationEnum::NOTIFICATION_PROJECT,
            NotificationEnum::NOTIFICATION_NEW_TASK,
        ];
        foreach ($switchedOff as $type) {
            $this->disableEmail($user, $type);
            $this->storeNotification($user, $type);
        }
        // E-Mail war die ganze Zeit an – dieser Eintrag gehört in die nächste Sammelmail
        $this->storeNotification($user, NotificationEnum::NOTIFICATION_TASK_CHANGED);

        // einzeln
        $team = $user->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)->sole();
        $this->patchJson(route('notifications.settings', $team), ['enabled_email' => true])->assertOk();
        $this->assertSame(0, $this->pendingCount($user, NotificationEnum::NOTIFICATION_TEAM));
        $this->assertSame(1, $this->pendingCount($user, NotificationEnum::NOTIFICATION_PROJECT));

        // Sammeländerung („alle an“ einer Gruppe)
        $this->patchJson(route('notifications.settings.bulk'), [
            'groupType' => NotificationGroupEnum::PROJECTS->value,
            'enabled_email' => true,
        ])->assertOk();
        $this->assertSame(0, $this->pendingCount($user, NotificationEnum::NOTIFICATION_PROJECT));
        $this->assertSame(1, $this->pendingCount($user, NotificationEnum::NOTIFICATION_NEW_TASK));

        // Standard wiederherstellen
        $this->postJson(route('notifications.settings.reset'))->assertOk();
        $this->assertSame(0, $this->pendingCount($user, NotificationEnum::NOTIFICATION_NEW_TASK));

        $this->assertSame(1, $this->pendingCount($user, NotificationEnum::NOTIFICATION_TASK_CHANGED));
    }

    #[Test]
    public function switching_email_off_and_on_again_keeps_what_was_waiting_for_the_summary(): void
    {
        $user = $this->userWithSettings();
        $this->actingAs($user);
        $type = NotificationEnum::NOTIFICATION_TEAM;
        $setting = $user->notificationSettings()->where('type', $type->value)->sole();
        // wartete schon vor dem Ausschalten auf die Sammelmail
        $waiting = $this->storeNotification($user, $type, now()->subDays(2));

        $this->travel(1)->days();
        $this->patchJson(route('notifications.settings', $setting), ['enabled_email' => false])->assertOk();
        $this->travel(1)->hours();
        $whileOff = $this->storeNotification($user, $type, now());
        // eine spätere Push-Sammeländerung darf den Zeitpunkt des Ausschaltens nicht verschieben
        $this->travel(1)->hours();
        $this->patchJson(route('notifications.settings.bulk'), ['enabled_push' => false])->assertOk();
        $this->travel(1)->hours();
        $this->patchJson(route('notifications.settings', $setting), ['enabled_email' => true])->assertOk();

        $pendingIds = $user->notifications()->where('sent_in_summary', false)->pluck('id')->all();
        $this->assertContains($waiting, $pendingIds);
        $this->assertNotContains($whileOff, $pendingIds);
    }

    #[Test]
    public function the_summary_shows_at_most_the_limit_per_group_and_marks_the_rest_too(): void
    {
        $user = $this->userWithSettings();
        $limit = SendNotificationsEmailSummariesCommand::MAX_ENTRIES_PER_GROUP;
        $start = Carbon::parse('2026-10-05 08:00');
        for ($i = 0; $i < $limit + 5; $i++) {
            $this->storeNotification($user, NotificationEnum::NOTIFICATION_TEAM, $start->copy()->addSeconds($i));
        }
        $newest = $this->storeNotification($user, NotificationEnum::NOTIFICATION_PROJECT, $start->copy()->addHour());
        $task = $this->storeNotification($user, NotificationEnum::NOTIFICATION_NEW_TASK);

        $this->travelTo(Carbon::parse('2026-10-06 09:00'));
        $this->artisan('artwork:send-notifications-email-summaries')->assertSuccessful();

        Mail::assertSent(NotificationSummary::class, 1);
        Mail::assertSent(NotificationSummary::class, function (NotificationSummary $mail) use ($limit, $newest, $task): bool {
            $projects = $mail->notifications[NotificationGroupEnum::PROJECTS->value];
            $tasks = $mail->notifications[NotificationGroupEnum::TASKS->value];

            return count($projects['notifications']) === $limit
                && $projects['count'] === $limit + 6
                && $projects['more'] === 6
                // neueste zuerst
                && $projects['notifications'][0]['model']->getKey() === $newest
                && $tasks['count'] === 1
                && $tasks['more'] === 0
                && $tasks['notifications'][0]['model']->getKey() === $task;
        });
        $this->assertSame(0, $user->notifications()->where('sent_in_summary', false)->count());

        // die nicht gezeigten kommen nicht in der nächsten Mail wieder
        $this->artisan('artwork:send-notifications-email-summaries')->assertSuccessful();
        Mail::assertSent(NotificationSummary::class, 1);
    }

    #[Test]
    public function the_summary_mail_names_the_hidden_entries_in_the_recipients_language(): void
    {
        $build = fn (string $language): string => (string) (new NotificationSummary(
            ['PROJECTS' => [
                'title' => 'Projekte',
                'count' => 57,
                'more' => 7,
                'notifications' => [['body' => ['title' => 'Teamänderung', 'description' => []], 'model' => null]],
            ]],
            'Max',
            'Artwork Testhaus',
            'system@example.test',
            'Artwork',
            $language,
            ['rooms' => [], 'eventTypes' => [], 'projects' => []]
        ))->render();

        $german = $build('de');
        $this->assertStringContainsString('Und 7 weitere', $german);
        $this->assertStringContainsString(NotificationMailPresenter::notificationsUrl(), $german);
        $this->assertStringContainsString('And 7 more', $build('en'));
    }

    #[Test]
    public function event_lines_use_names_loaded_once_for_the_whole_mail(): void
    {
        $room = Room::factory()->create(['name' => 'Kleiner Saal']);
        $eventType = EventType::factory()->create(['name' => 'Probe']);
        $project = Project::factory()->create(['name' => 'Sommerfest']);
        $payloads = [
            ['event' => [
                'room_id' => $room->id,
                'event_type_id' => $eventType->id,
                'project_id' => $project->id,
                'eventName' => 'Durchlauf',
            ]],
            ['event' => ['room_id' => 999999999, 'eventName' => 'Ohne Raum']],
            ['title' => 'ohne Termin'],
        ];

        $lookups = NotificationMailPresenter::eventLookups($payloads);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $withLookups = array_map(
            fn (array $payload): string => NotificationMailPresenter::eventLine($payload['event'] ?? null, 'de', $lookups),
            $payloads
        );
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();

        $individually = array_map(
            fn (array $payload): string => NotificationMailPresenter::eventLine($payload['event'] ?? null, 'de'),
            $payloads
        );
        $this->assertSame($individually, $withLookups);
        $this->assertSame('Kleiner Saal | Probe | Durchlauf | Sommerfest', $withLookups[0]);
    }

    #[Test]
    public function the_update_command_only_marks_the_backlog_of_accounts_missing_a_setting(): void
    {
        $settingService = app(NotificationSettingService::class);
        $complete = $this->userWithSettings();
        $missing = $this->userWithSettings();
        $settingService->ensureDefaultsForAllUsers();
        $this->storeNotification($complete, NotificationEnum::NOTIFICATION_TEAM);
        $this->storeNotification($missing, NotificationEnum::NOTIFICATION_TEAM);

        // niemandem fehlt etwas → kein UPDATE auf notifications (vorher je Typ ein Full-Scan)
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(0, $settingService->ensureDefaultsForAllUsers());
        $notificationUpdates = array_filter(
            DB::getQueryLog(),
            static fn (array $query): bool => preg_match('/^update\s+`?notifications`?\s/i', $query['query']) === 1
        );
        DB::disableQueryLog();
        $this->assertSame([], $notificationUpdates);
        $this->assertSame(1, $this->pendingCount($missing, NotificationEnum::NOTIFICATION_TEAM));

        $missing->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)->delete();
        $this->assertSame(1, $settingService->ensureDefaultsForAllUsers());

        $this->assertSame(0, $this->pendingCount($missing, NotificationEnum::NOTIFICATION_TEAM));
        $this->assertSame(1, $this->pendingCount($complete, NotificationEnum::NOTIFICATION_TEAM));
    }

    #[Test]
    public function archiving_all_reaches_every_entry_beyond_the_first_chunk(): void
    {
        $user = User::factory()->create();
        $total = 1200;
        $start = Carbon::parse('2026-01-01 08:00');
        // id-Reihenfolge = Erstellreihenfolge: mit created_at DESC vor dem id-Cursor brach chunkById nach
        // dem ersten Block ab (alle späteren IDs waren schon archiviert)
        for ($i = 1; $i <= $total; $i++) {
            $this->storeNotification(
                $user,
                NotificationEnum::NOTIFICATION_TEAM,
                $start->copy()->addSeconds($i),
                sprintf('%08d-0000-4000-8000-000000000000', $i)
            );
        }

        (new ArchiveUserNotificationsJob($user->id))->handle(app(DatabaseNotificationService::class));

        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());
        $this->assertSame(
            30,
            app(DatabaseNotificationService::class)->archiveAllUnreadForUser($this->freshUserWith(30), null, 10)
        );
    }

    private function freshUserWith(int $count): User
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= $count; $i++) {
            $this->storeNotification(
                $user,
                NotificationEnum::NOTIFICATION_PROJECT,
                Carbon::parse('2026-01-01 08:00')->addMinutes($i),
                sprintf('%08d-1111-4000-8000-%012d', $i, $user->id)
            );
        }

        return $user;
    }
}
