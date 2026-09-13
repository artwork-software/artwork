<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Support\NotificationMailPresenter;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Feature\FeatureTestCase;

/**
 * Block 3a: Sofort-Mail (emails.simple-mail) und Sammelmail (emails.notifications)
 * rendern die Beschreibungszeilen (Datum, Zeit, Gewerk, Projekt) und verlinken auf
 * den Deep-Link der Notification statt pauschal auf die App-URL.
 */
final class NotificationMailTemplateTest extends FeatureTestCase
{
    /**
     * Beispiel-Payload wie ihn NotificationService::createNotification baut.
     */
    private function lockedShiftPayload(User $worker): stdClass
    {
        $link = route('user.operationPlan', [
            'user' => $worker->id,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
        ]);

        $body = new stdClass();
        $body->icon = 'green';
        $body->priority = 3;
        $body->groupType = NotificationEnum::NOTIFICATION_SHIFT_LOCKED->groupType();
        $body->type = NotificationEnum::NOTIFICATION_SHIFT_LOCKED;
        $body->title = 'Dein Dienstplan Technik KW 19/2026 wurde festgeschrieben';
        $body->description = [
            0 => [
                'type' => 'text',
                'title' => 'Betrifft Zeitraum: 04.05.2026 - 10.05.2026',
                'href' => $link,
            ],
            1 => [
                'type' => 'link',
                'title' => 'Zu meinem Einsatzplan',
                'href' => $link,
            ],
        ];
        $body->buttons = [];
        $body->showHistory = false;
        $body->historyType = '';
        $body->modelId = null;
        $body->roomId = null;
        $body->eventId = null;
        $body->event = null;
        $body->projectId = null;
        $body->departmentId = null;
        $body->taskId = null;
        $body->created_by = null;
        $body->created_at = '06.09.2026 10:00';
        $body->budgetData = null;
        $body->notificationKey = '';
        $body->shiftId = null;
        $body->positionVerifyRequestId = null;
        $body->positionVerifyRequestType = null;

        return $body;
    }

    #[Test]
    public function immediate_mail_renders_description_and_deep_link(): void
    {
        $worker = User::factory()->create();
        $body = $this->lockedShiftPayload($worker);

        $rendered = (string) (new ShiftNotification($body, []))->toMail()->render();

        $this->assertStringContainsString('Dein Dienstplan Technik KW 19/2026 wurde festgeschrieben', $rendered);
        $this->assertStringContainsString('Betrifft Zeitraum: 04.05.2026 - 10.05.2026', $rendered);
        $this->assertStringContainsString('operation/plan', $rendered);
        $this->assertStringContainsString('start_date=2026-05-04', $rendered);
        // Die App-URL bleibt als Fallback-Link ("alle Benachrichtigungen") erhalten
        $this->assertStringContainsString(NotificationMailPresenter::appUrl(), $rendered);
    }

    #[Test]
    public function immediate_mail_without_deep_link_falls_back_to_app_url(): void
    {
        $worker = User::factory()->create();
        $body = $this->lockedShiftPayload($worker);
        $body->description = [
            1 => ['type' => 'string', 'title' => 'Deine Schicht: 06.05.2026 09:00 - 17:00', 'href' => null],
        ];

        $rendered = (string) (new ShiftNotification($body, []))->toMail()->render();

        $this->assertStringContainsString('Deine Schicht: 06.05.2026 09:00 - 17:00', $rendered);
        $this->assertStringNotContainsString('operation/plan', $rendered);
        $this->assertStringContainsString('href="' . NotificationMailPresenter::appUrl() . '"', $rendered);
    }

    #[Test]
    public function summary_mail_renders_description_and_deep_link_per_entry(): void
    {
        $worker = User::factory()->create();
        // Sammelmail arbeitet mit dem JSON-dekodierten Array einer DatabaseNotification
        $data = json_decode(json_encode($this->lockedShiftPayload($worker)), true);

        $mailable = new NotificationSummary(
            [
                'shift' => [
                    'title' => 'Dienstplan',
                    'count' => 1,
                    'notifications' => [
                        ['body' => $data, 'model' => null],
                    ],
                ],
            ],
            'Max',
            'Artwork Testhaus',
            'system@example.test',
            'Artwork'
        );

        $rendered = (string) $mailable->render();

        $this->assertStringContainsString('Dein Dienstplan Technik KW 19/2026 wurde festgeschrieben', $rendered);
        $this->assertStringContainsString('Betrifft Zeitraum: 04.05.2026 - 10.05.2026', $rendered);
        $this->assertStringContainsString('operation/plan', $rendered);
        $this->assertStringContainsString('start_date=2026-05-04', $rendered);
    }

    #[Test]
    public function presenter_completes_relative_links_and_ignores_empty_ones(): void
    {
        $appUrl = NotificationMailPresenter::appUrl();

        $this->assertSame($appUrl . '/shifts/view', NotificationMailPresenter::absoluteUrl('/shifts/view'));
        $this->assertSame('https://example.test/x', NotificationMailPresenter::absoluteUrl('https://example.test/x'));
        $this->assertNull(NotificationMailPresenter::absoluteUrl(''));
        $this->assertNull(NotificationMailPresenter::absoluteUrl(null));
        $this->assertNull(NotificationMailPresenter::absoluteUrl('javascript:alert(1)'));

        $description = [
            ['type' => 'string', 'title' => 'Zeile ohne Link', 'href' => null],
            ['type' => 'link', 'title' => 'Zum Dienstplan', 'href' => '/shifts/view'],
        ];
        $this->assertSame(['Zeile ohne Link'], NotificationMailPresenter::textLines($description));
        $this->assertSame($appUrl . '/shifts/view', NotificationMailPresenter::primaryLink($description));
        $this->assertSame($appUrl, NotificationMailPresenter::primaryLink(null));
    }
}
