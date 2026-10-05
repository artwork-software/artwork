<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Events\NewNotificationBroadcast;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Benachrichtigungseinstellungen: Standardwerte ohne Überschreiben, Live-Hinweis/Glocke nur bei
 * Zustellung, Statusfarbe, Validierung der Endpunkte, keine wirkungslosen Schalter.
 */
final class NotificationSettingsBehaviourTest extends FeatureTestCase
{
    private function settingOf(User $user, NotificationEnum $type): NotificationSetting
    {
        return $user->notificationSettings()->where('type', $type->value)->sole();
    }

    #[Test]
    public function missing_settings_are_added_with_defaults_but_existing_choices_are_kept(): void
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        $roomAnswer = $this->settingOf($user, NotificationEnum::NOTIFICATION_ROOM_ANSWER);
        $roomAnswer->update(['enabled_email' => false, 'enabled_push' => false]);
        $newType = $this->settingOf($user, NotificationEnum::NOTIFICATION_TEAM);
        $newType->delete();

        // vorher: artwork:update setzte E-Mail/Push für 9 Typen bei jedem Lauf wieder auf an
        $created = app(NotificationSettingService::class)->ensureDefaultsForAllUsers();

        $this->assertGreaterThanOrEqual(1, $created);
        $this->assertFalse($roomAnswer->fresh()->enabled_email);
        $this->assertFalse($roomAnswer->fresh()->enabled_push);
        $this->assertTrue($this->settingOf($user, NotificationEnum::NOTIFICATION_TEAM)->enabled_email);
        $this->assertSame(
            NotificationFrequencyEnum::IMMEDIATELY,
            $this->settingOf($user, NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED)->frequency
        );
        $this->assertFalse($user->notificationSettings()
            ->where('type', NotificationEnum::NOTIFICATION_REMINDER_ROOM_REQUEST->value)->exists());
        $this->assertSame(0, app(NotificationSettingService::class)->ensureDefaultsForAllUsers());
    }

    #[Test]
    public function the_update_command_no_longer_resets_user_choices(): void
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        $this->settingOf($user, NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS)
            ->update(['enabled_email' => false, 'frequency' => NotificationFrequencyEnum::WEEKLY_ONCE]);

        $method = new \ReflectionMethod(\Artwork\Core\Console\Commands\UpdateArtwork::class, 'updateNotificationSettings');
        $command = app(\Artwork\Core\Console\Commands\UpdateArtwork::class);
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput()
        ));
        $method->invoke($command);

        $setting = $this->settingOf($user, NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);
        $this->assertFalse($setting->enabled_email);
        $this->assertSame(NotificationFrequencyEnum::WEEKLY_ONCE, $setting->frequency);
    }

    private function notify(User $recipient, NotificationEnum $type, string $icon = 'gray'): void
    {
        $service = app(NotificationService::class);
        $service->setNotificationConstEnum($type);
        $service->setNotificationTo($recipient);
        $service->setTitle('Test');
        $service->setIcon($icon);
        $service->setBroadcastMessage(['type' => 'success', 'message' => 'Test']);
        $service->createNotification();
    }

    #[Test]
    public function the_bell_only_lights_up_when_a_notification_is_delivered(): void
    {
        Event::fake([NewNotificationBroadcast::class]);
        $actor = $this->actingAsAdmin(User::factory()->create(['show_notification_indicator' => false]));
        app(NotificationSettingService::class)->ensureDefaultsForUser($actor);
        $recipient = User::factory()->create(['show_notification_indicator' => false]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($recipient);

        // eigene Aktion: Toast als Rückmeldung (gewollt), aber kein Eintrag → keine Glocke
        $this->notify($actor, NotificationEnum::NOTIFICATION_TEAM);
        Event::assertDispatched(NewNotificationBroadcast::class, 1);
        $this->assertFalse((bool) $actor->fresh()->show_notification_indicator);

        $this->notify($recipient, NotificationEnum::NOTIFICATION_TEAM);
        Event::assertDispatched(NewNotificationBroadcast::class, 2);
        $this->assertTrue((bool) $recipient->fresh()->show_notification_indicator);

        // Push abgeschaltet: kein Toast
        $this->settingOf($recipient, NotificationEnum::NOTIFICATION_PROJECT)->update(['enabled_push' => false]);
        $this->notify($recipient, NotificationEnum::NOTIFICATION_PROJECT);
        Event::assertDispatched(NewNotificationBroadcast::class, 2);
    }

    #[Test]
    public function the_status_colour_reaches_the_notification(): void
    {
        $this->actingAsAdmin();
        $recipient = User::factory()->create();
        \Illuminate\Support\Facades\Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));

        $this->notify($recipient, NotificationEnum::NOTIFICATION_TEAM, 'red');
        $this->notify($recipient, NotificationEnum::NOTIFICATION_PROJECT, 'warning');
        $this->notify($recipient, NotificationEnum::NOTIFICATION_NEW_TASK, 'unknown');

        $icons = $recipient->notifications()->get()->mapWithKeys(
            fn ($notification) => [$notification->data['type'] => $notification->data['icon']]
        );
        // vorher immer 'gray'
        $this->assertSame('red', $icons[NotificationEnum::NOTIFICATION_TEAM->value]);
        $this->assertSame('red', $icons[NotificationEnum::NOTIFICATION_PROJECT->value]);
        $this->assertSame('gray', $icons[NotificationEnum::NOTIFICATION_NEW_TASK->value]);
    }

    #[Test]
    public function settings_endpoints_validate_their_input(): void
    {
        $user = $this->actingAsUserWith([]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        $setting = $this->settingOf($user, NotificationEnum::NOTIFICATION_TEAM);

        $this->patchJson(route('notifications.settings', $setting), ['frequency' => 'hourly'])
            ->assertUnprocessable()->assertJsonValidationErrors('frequency');
        $this->patchJson(route('notifications.settings', $setting), ['enabled_email' => 'vielleicht'])
            ->assertUnprocessable();
        $this->patchJson(route('notifications.settings', $setting), [
            'enabled_email' => false,
            'frequency' => NotificationFrequencyEnum::WEEKLY_TWICE->value,
        ])->assertOk();
        $this->assertFalse($setting->fresh()->enabled_email);

        $this->patchJson(route('notifications.group'), ['groupType' => 'NOPE', 'enabled_push' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('groupType');
        $this->patchJson(route('notifications.group'), [
            'groupType' => NotificationGroupEnum::PROJECTS->value,
            'enabled_push' => false,
        ])->assertOk();
        $this->assertFalse($this->settingOf($user, NotificationEnum::NOTIFICATION_TEAM)->enabled_push);

        $other = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($other);
        $this->patchJson(
            route('notifications.settings', $this->settingOf($other, NotificationEnum::NOTIFICATION_TEAM)),
            ['enabled_email' => false]
        )->assertForbidden();
    }

    #[Test]
    public function shift_rule_violation_mails_go_to_a_queue_the_worker_processes(): void
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        $this->settingOf($user, NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT)
            ->update(['frequency' => NotificationFrequencyEnum::IMMEDIATELY]);
        \Illuminate\Support\Facades\Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));
        $violation = \Artwork\Modules\Shift\Models\ShiftRuleViolation::factory()->create();

        $user->notify(new \Artwork\Modules\Workflow\Notifications\ShiftRuleViolationNotification($violation, 'Zu lange Schicht'));

        // vorher Queue "sync" – der Worker hört nur auf default,webhooks,workflows
        \Illuminate\Support\Facades\Bus::assertDispatched(
            \Illuminate\Notifications\SendQueuedNotifications::class,
            fn ($job) => in_array('mail', $job->channels, true) && $job->queue !== 'sync'
        );
        \Illuminate\Support\Facades\Bus::assertNotDispatched(
            \Illuminate\Notifications\SendQueuedNotifications::class,
            fn ($job) => $job->queue === 'sync'
        );
    }
}
