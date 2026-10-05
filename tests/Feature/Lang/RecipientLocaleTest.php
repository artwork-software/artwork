<?php

namespace Tests\Feature\Lang;

use Artwork\Modules\Department\Notifications\TeamNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Benachrichtigungen werden in der Sprache der Empfänger:in gerendert, nicht in der Sprache
 * der Person, die sie auslöst.
 */
final class RecipientLocaleTest extends FeatureTestCase
{
    #[Test]
    public function the_preferred_locale_is_the_users_language_when_supported(): void
    {
        $this->assertSame('en', User::factory()->make(['language' => 'en'])->preferredLocale());
        $this->assertSame('de', User::factory()->make(['language' => 'de'])->preferredLocale());
        $this->assertNull(User::factory()->make(['language' => 'xx'])->preferredLocale());
    }

    #[Test]
    public function notifications_carry_the_recipients_locale(): void
    {
        $this->actingAsAdmin(User::factory()->create(['language' => 'de']));
        App::setLocale('de');
        $recipient = User::factory()->create(['language' => 'en']);

        $service = app(NotificationService::class);
        $service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_TEAM);
        $service->setNotificationTo($recipient);
        $service->setTitle('Team');
        $service->setBroadcastMessage([]);
        $service->createNotification();

        Notification::assertSentTo(
            $recipient,
            TeamNotification::class,
            fn ($notification, array $channels, $notifiable, ?string $locale): bool => $locale === 'en'
        );
    }
}
