<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationSender;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Die Sofort-Mail an die einladende Person hängt an deren Benachrichtigungseinstellung
 * (E-Mail aktiv + Frequenz „sofort“). Ohne Einstellung bleibt es bei der Datenbank-Meldung.
 */
final class ImmediateMailChannelTest extends TestCase
{
    /**
     * @return array{0:ExternalAccess,1:User}
     */
    private function externalWithInviter(): array
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $inviter = User::factory()->create();
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $fl->createCrmContact();
        $external = ExternalAccess::factory()->create([
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        return [$external, $inviter];
    }

    private function setting(User $user, NotificationFrequencyEnum $frequency, bool $email = true): void
    {
        NotificationSetting::query()->create([
            'user_id' => $user->id,
            'group_type' => NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->groupType(),
            'type' => NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED,
            'title' => 'External person submitted shared tab data',
            'description' => 'test',
            'frequency' => $frequency,
            'enabled_email' => $email,
            'enabled_push' => false,
        ]);
    }

    /**
     * @return string[]
     */
    private function channelsUsedFor(User $inviter): array
    {
        $channels = [];
        Notification::assertSentTo(
            $inviter,
            ExternalTabComponentUpdatedNotification::class,
            function (ExternalTabComponentUpdatedNotification $notification, array $usedChannels) use (&$channels): bool {
                $channels = $usedChannels;

                return true;
            },
        );

        return $channels;
    }

    private function submit(ExternalAccess $external): void
    {
        app(ExternalNotificationSender::class)->notifyTabSubmitted(
            $external,
            Project::factory()->create(),
            ProjectTab::factory()->create(),
            1,
        );
    }

    #[Test]
    public function immediately_with_email_enabled_uses_mail_channel(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalWithInviter();
        $this->setting($inviter, NotificationFrequencyEnum::IMMEDIATELY);

        $this->submit($external);

        $channels = $this->channelsUsedFor($inviter);
        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
    }

    #[Test]
    public function daily_frequency_does_not_use_mail_channel(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalWithInviter();
        $this->setting($inviter, NotificationFrequencyEnum::DAILY);

        $this->submit($external);

        $channels = $this->channelsUsedFor($inviter);
        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
    }

    #[Test]
    public function immediately_with_email_disabled_does_not_use_mail_channel(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalWithInviter();
        $this->setting($inviter, NotificationFrequencyEnum::IMMEDIATELY, email: false);

        $this->submit($external);

        $this->assertNotContains('mail', $this->channelsUsedFor($inviter));
    }

    #[Test]
    public function without_setting_only_database_channel_is_used(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalWithInviter();

        $this->submit($external);

        $this->assertSame(['database'], $this->channelsUsedFor($inviter));
    }
}
