<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Zusammenfassungen an festen Wochentagen (täglich / Mo+Do / Mo) statt „x Tage nach der letzten
 * Mail je Typ“, kein Doppelversand nach „sofort“, Rückmeldung beim Archivieren, Einstellungslink.
 */
final class NotificationSummaryAndCentreTest extends FeatureTestCase
{
    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::swap(new ChannelManager($this->app));
        $this->actingAsAdmin();
        $this->recipient = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($this->recipient);
    }

    private function frequency(NotificationEnum $type, NotificationFrequencyEnum $frequency): void
    {
        $this->recipient->notificationSettings()->where('type', $type->value)->update(['frequency' => $frequency->value]);
    }

    private function notify(NotificationEnum $type): void
    {
        $service = app(NotificationService::class);
        $service->clearNotificationData();
        $service->setNotificationConstEnum($type);
        $service->setNotificationTo($this->recipient);
        $service->setTitle('Test ' . $type->value);
        $service->setBroadcastMessage([]);
        $service->createNotification();
    }

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function weekdays(): array
    {
        return [
            'Montag: alle' => ['2026-10-05', ['daily', 'twice', 'weekly']],
            'Dienstag: nur täglich' => ['2026-10-06', ['daily']],
            'Donnerstag: täglich + zweimal' => ['2026-10-08', ['daily', 'twice']],
        ];
    }

    #[Test]
    #[DataProvider('weekdays')]
    public function summaries_follow_fixed_weekdays(string $day, array $expectedFrequencies): void
    {
        $types = [
            'daily' => NotificationEnum::NOTIFICATION_TEAM,
            'twice' => NotificationEnum::NOTIFICATION_PROJECT,
            'weekly' => NotificationEnum::NOTIFICATION_NEW_TASK,
        ];
        $this->frequency($types['twice'], NotificationFrequencyEnum::WEEKLY_TWICE);
        $this->frequency($types['weekly'], NotificationFrequencyEnum::WEEKLY_ONCE);
        foreach ($types as $type) {
            $this->notify($type);
        }

        $this->travelTo(Carbon::parse($day . ' 09:00'));
        $this->artisan('artwork:send-notifications-email-summaries')->assertSuccessful();

        Mail::assertSent(NotificationSummary::class, 1);
        foreach ($types as $key => $type) {
            $sent = $this->recipient->notifications()
                ->whereJsonContains('data->type', $type->value)
                ->value('sent_in_summary');
            $this->assertSame(in_array($key, $expectedFrequencies, true), (bool) $sent, $key);
        }
    }

    #[Test]
    public function leaving_immediate_mail_does_not_mail_the_same_notifications_again(): void
    {
        $this->frequency(NotificationEnum::NOTIFICATION_TEAM, NotificationFrequencyEnum::IMMEDIATELY);
        $this->notify(NotificationEnum::NOTIFICATION_TEAM);
        $this->actingAs($this->recipient);
        $setting = $this->recipient->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)->sole();

        $this->patchJson(route('notifications.settings', $setting), ['frequency' => NotificationFrequencyEnum::DAILY->value])
            ->assertOk();
        $this->travelTo(Carbon::parse('2026-10-06 09:00'));
        $this->artisan('artwork:send-notifications-email-summaries')->assertSuccessful();

        Mail::assertNotSent(NotificationSummary::class);
    }

    #[Test]
    public function settings_added_later_do_not_mail_the_old_backlog(): void
    {
        $this->notify(NotificationEnum::NOTIFICATION_TEAM);
        $this->notify(NotificationEnum::NOTIFICATION_PROJECT);
        // Konten von vor Paket A hatten nur einen Teil der Typen – hier fehlt NOTIFICATION_TEAM
        $this->recipient->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)->delete();

        app(NotificationSettingService::class)->ensureDefaultsForAllUsers();
        $this->travelTo(Carbon::parse('2026-10-05 09:00'));
        $this->artisan('artwork:send-notifications-email-summaries')->assertSuccessful();

        Mail::assertSent(NotificationSummary::class, 1);
        $summarised = fn (NotificationEnum $type): bool => (bool) $this->recipient->notifications()
            ->whereJsonContains('data->type', $type->value)
            ->value('sent_in_summary');
        $this->assertTrue($summarised(NotificationEnum::NOTIFICATION_TEAM));
        $this->assertTrue($summarised(NotificationEnum::NOTIFICATION_PROJECT));
        Mail::assertSent(
            NotificationSummary::class,
            fn (NotificationSummary $mail): bool => !str_contains(json_encode($mail->notifications), 'NOTIFICATION_TEAM')
        );
    }

    #[Test]
    public function archiving_all_reports_what_happened(): void
    {
        $this->notify(NotificationEnum::NOTIFICATION_TEAM);
        $this->notify(NotificationEnum::NOTIFICATION_PROJECT);
        $this->recipient->notifications()->latest()->first()->update([
            'data' => array_merge($this->recipient->notifications()->latest()->first()->data, ['buttons' => ['accept_something']]),
        ]);
        $this->actingAs($this->recipient);

        $this->patchJson(route('notifications.setReadAtAll'), ['groupType' => 'PROJECTS'])
            ->assertOk()
            ->assertJson(['archived' => 1, 'remaining' => 1, 'queued' => false]);
    }

    #[Test]
    public function mails_link_to_the_notification_settings(): void
    {
        $this->frequency(NotificationEnum::NOTIFICATION_TEAM, NotificationFrequencyEnum::IMMEDIATELY);
        $data = new \stdClass();
        $data->type = NotificationEnum::NOTIFICATION_TEAM;
        $data->title = 'Teamänderung';
        $data->description = [];
        $data->buttons = [];

        $html = (string) (new \Artwork\Modules\Department\Notifications\TeamNotification($data, []))
            ->toMail($this->recipient)
            ->render();

        $this->assertStringContainsString('tab=settings', $html);
        $this->assertStringContainsString('type=NOTIFICATION_TEAM', $html);
    }
}
