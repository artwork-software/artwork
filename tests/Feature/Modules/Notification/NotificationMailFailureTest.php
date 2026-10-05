<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * Sofort-Mails laufen synchron. Ein nicht erreichbarer Mailserver darf die auslösende
 * Aktion nicht mit 500 abbrechen; die Benachrichtigung in der App bleibt erhalten.
 * Bewusst ohne FeatureTestCase: der fakt Notification und Mail global.
 */
final class NotificationMailFailureTest extends TestCase
{
    #[Test]
    public function an_unreachable_mail_server_does_not_break_the_notification(): void
    {
        Mail::extend('failing', fn () => new class extends AbstractTransport {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Connection could not be established');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });
        config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);
        $exceptions = Exceptions::fake();

        $recipient = User::factory()->create(['language' => 'de']);
        $recipient->notificationSettings()->create([
            'group_type' => NotificationEnum::NOTIFICATION_PROJECT->groupType(),
            'type' => NotificationEnum::NOTIFICATION_PROJECT->value,
            'title' => NotificationEnum::NOTIFICATION_PROJECT->title(),
            'description' => NotificationEnum::NOTIFICATION_PROJECT->description(),
            'frequency' => NotificationFrequencyEnum::IMMEDIATELY->value,
            'enabled_email' => true,
            'enabled_push' => false,
        ]);
        $this->actingAs(User::factory()->create());

        $service = app(NotificationService::class);
        $service->setTitle('Du wurdest zu einem Projekt hinzugefügt');
        $service->setIcon('green');
        $service->setPriority(3);
        $service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
        $service->setNotificationTo($recipient);
        $service->setDescription([]);
        $service->createNotification();

        $this->assertSame(1, $recipient->notifications()->count());
        $exceptions->assertReported(TransportException::class);
    }
}
