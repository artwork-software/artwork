<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Support\NotificationMailPresenter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
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

        $rendered = (string) (new ShiftNotification($body, []))->toMail($worker)->render();

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

        $rendered = (string) (new ShiftNotification($body, []))->toMail($worker)->render();

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

    /**
     * Termin mit Raum, Terminart und Projekt, die jeweils NICHT der erste Datensatz ihrer Tabelle sind.
     * Kundenbefund (Release ≤ 1.7.5): find($id)->first() lieferte den ERSTEN Raum/Typ/Projekt der DB.
     *
     * @return array{event: Event, wrong: array<string, string>, right: array<string, string>}
     */
    private function eventWithDecoys(): array
    {
        $firstRoom = Room::factory()->create(['name' => 'Erster Raum (falsch)']);
        $firstType = EventType::factory()->create(['name' => 'Erste Terminart (falsch)']);
        $firstProject = Project::factory()->create(['name' => 'Erstes Projekt (falsch)']);

        $room = Room::factory()->create(['name' => 'Studio 7']);
        $type = EventType::factory()->create(['name' => 'Probe']);
        $project = Project::factory()->create(['name' => 'Sommerfestival']);
        $event = Event::factory()->create([
            'eventName' => 'Hauptprobe Akt II',
            'room_id' => $room->id,
            'event_type_id' => $type->id,
            'project_id' => $project->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 11:30:00',
        ]);

        return [
            'event' => $event,
            'wrong' => ['room' => $firstRoom->name, 'type' => $firstType->name, 'project' => $firstProject->name],
            'right' => ['room' => $room->name, 'type' => $type->name, 'project' => $project->name],
        ];
    }

    private function eventPayload(User $recipient, Event $event): stdClass
    {
        $body = $this->lockedShiftPayload($recipient);
        $body->type = NotificationEnum::NOTIFICATION_ROOM_REQUEST;
        $body->groupType = NotificationEnum::NOTIFICATION_ROOM_REQUEST->groupType();
        $body->title = 'Neue Raumanfrage';
        $body->description = [
            1 => ['type' => 'string', 'title' => '12.10.2026 09:00 - 12.10.2026 11:30', 'href' => null],
        ];
        $body->eventId = $event->id;
        $body->event = $event;

        return $body;
    }

    #[Test]
    public function immediate_mail_shows_the_events_own_room_type_project_and_time(): void
    {
        ['event' => $event, 'wrong' => $wrong, 'right' => $right] = $this->eventWithDecoys();
        $recipient = User::factory()->create(['language' => 'de']);

        $rendered = (string) (new EventNotification($this->eventPayload($recipient, $event), []))
            ->toMail($recipient)
            ->render();

        $this->assertStringContainsString('Neue Raumanfrage', $rendered);
        foreach ($right as $name) {
            $this->assertStringContainsString($name, $rendered);
        }
        foreach ($wrong as $name) {
            $this->assertStringNotContainsString($name, $rendered);
        }
        $this->assertStringContainsString('Hauptprobe Akt II', $rendered);
        $this->assertStringContainsString('12.10.2026 09:00 - 12.10.2026 11:30', $rendered);
        $this->assertStringContainsString('Alle Benachrichtigungen in', $rendered);
        // CSS liegt im HTML-Layout und wird beim Rendern inline gesetzt (Link-Farbe der Notification-Mails)
        $this->assertStringContainsString('color: #3017AD', $rendered);
    }

    #[Test]
    public function summary_mail_shows_the_events_own_room_type_project_from_database_payload(): void
    {
        ['event' => $event, 'wrong' => $wrong, 'right' => $right] = $this->eventWithDecoys();
        $recipient = User::factory()->create(['language' => 'de']);
        // Sammelmail arbeitet mit dem JSON-Roundtrip der DatabaseNotification (Event als Array,
        // Zeiten im Cast-Format "12. Oct 2026 09:00")
        $data = json_decode(json_encode($this->eventPayload($recipient, $event)), true);
        $this->assertSame('12. Oct 2026 09:00', $data['event']['start_time']);

        $rendered = (string) (new NotificationSummary(
            ['event' => ['title' => 'Termine', 'count' => 1, 'notifications' => [['body' => $data, 'model' => null]]]],
            'Max',
            'Artwork Testhaus',
            'system@example.test',
            'Artwork',
            'de'
        ))->render();

        foreach ($right as $name) {
            $this->assertStringContainsString($name, $rendered);
        }
        foreach ($wrong as $name) {
            $this->assertStringNotContainsString($name, $rendered);
        }
        $this->assertStringContainsString('12.10.2026 09:00 - 12.10.2026 11:30', $rendered);
        $this->assertStringContainsString('Hallo Max,', $rendered);
        $this->assertStringContainsString('Es gibt Neuigkeiten in Artwork Testhaus', $rendered);
    }

    #[Test]
    public function mail_texts_follow_the_recipients_language(): void
    {
        ['event' => $event] = $this->eventWithDecoys();
        $recipient = User::factory()->create(['language' => 'en']);
        $body = $this->eventPayload($recipient, $event);
        $body->title = 'New room request';

        $rendered = (string) (new EventNotification($body, []))->toMail($recipient)->render();

        $this->assertStringContainsString('View all notifications in', $rendered);
        $this->assertStringNotContainsString('Alle Benachrichtigungen', $rendered);

        $summary = (string) (new NotificationSummary(
            ['event' => ['title' => 'Events', 'count' => 1, 'notifications' => [
                ['body' => json_decode(json_encode($body), true), 'model' => null],
            ]]],
            'Max',
            'Artwork Testhaus',
            'system@example.test',
            'Artwork',
            'en'
        ))->render();

        $this->assertStringContainsString('Hello Max,', $summary);
        $this->assertStringContainsString('There is news in Artwork Testhaus', $summary);
        $this->assertStringNotContainsString('Hallo', $summary);
    }

    #[Test]
    public function plain_text_part_contains_no_stylesheet_rules(): void
    {
        $worker = User::factory()->create();
        $mail = (new ShiftNotification($this->lockedShiftPayload($worker), []))->toMail($worker);
        $markdown = app(\Illuminate\Mail\Markdown::class);

        $text = (string) $markdown->renderText($mail->markdown, $mail->data());

        $this->assertStringContainsString('Dein Dienstplan Technik KW 19/2026 wurde festgeschrieben', $text);
        $this->assertStringNotContainsString('font-family', $text);
        $this->assertStringNotContainsString('margin-bottom', $text);
    }

    #[Test]
    public function event_line_resolves_by_id_and_tolerates_missing_or_invalid_values(): void
    {
        ['event' => $event, 'right' => $right] = $this->eventWithDecoys();

        $line = NotificationMailPresenter::eventLine($event, 'de');
        $this->assertSame(
            $right['room'] . ' | ' . $right['type'] . ' | Hauptprobe Akt II | ' . $right['project']
                . ' | 12.10.2026 09:00 - 12.10.2026 11:30',
            $line
        );

        $this->assertSame('', NotificationMailPresenter::eventLine(null, 'de'));
        $this->assertSame('', NotificationMailPresenter::eventLine([], 'de'));
        $this->assertSame(
            'Termin ohne Raum | Ohne Typ',
            NotificationMailPresenter::eventLine(
                ['room_id' => 999999, 'eventName' => 'Ohne Typ', 'start_time' => 'kein datum'],
                'de'
            )
        );
    }
}
