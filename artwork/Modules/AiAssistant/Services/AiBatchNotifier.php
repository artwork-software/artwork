<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Sammelmeldung und Kalender-Refresh nach einem ausgefuehrten KI-Batch
 * (core-notification-buendelung; Konzept W4).
 *
 * Der KI-Schreibpfad laeuft ueber die Bulk-Services, nicht ueber die Controller der Oberflaeche —
 * und die Benachrichtigungen des Bestands haengen an den Controllern. Ohne diese Klasse bliebe der
 * Assistent still (Befund in §1 des Paket-Logs).
 *
 * Gebuendelt wird pro Batch und Projekt: eine Meldung je Empfaenger statt einer je Termin.
 */
class AiBatchNotifier
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_REVERTED = 'reverted';

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
    ) {
    }

    /**
     * @param list<Event>|Collection<int, Event> $events
     */
    public function notify(User $causer, string $action, iterable $events): void
    {
        $events = Collection::make($events)->filter(static fn ($event): bool => $event instanceof Event);

        if ($events->isEmpty()) {
            return;
        }

        // Ein Batch betrifft im Regelfall genau ein Projekt; bei `delete_events` koennen es
        // mehrere sein. Dann gibt es je Projekt eine Meldung — sonst stuende im Text ein Projekt,
        // das nur ein Teil der Termine betrifft.
        foreach ($events->groupBy(static fn (Event $event): string => (string) $event->project_id) as $group) {
            $this->notifyGroup($causer, $action, $group);
        }

        // Ein payloadloser Refresh fuer den ganzen Batch statt eines Broadcasts je Termin (W4 §2).
        // Dasselbe Muster wie `EventService::deleteAll()`.
        broadcast(new OccupancyUpdated())->toOthers();
    }

    /**
     * @param Collection<int, Event> $events
     */
    private function notifyGroup(User $causer, string $action, Collection $events): void
    {
        $recipients = $this->recipients($events, $causer);

        if ($recipients->isEmpty()) {
            return;
        }

        $first = $events->first();
        $project = $first?->project;

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_CHANGED);
        $this->notificationService->setProjectId($first?->project_id);
        $this->notificationService->setCreatedBy($causer);

        foreach ($recipients as $recipient) {
            $title = trans_choice(
                'notification.ai_assistant.' . $action,
                $events->count(),
                ['count' => $events->count()],
                $recipient->language
            );

            // Ein eigener Schluessel je Meldung: sonst fasst die Oberflaeche die Sammelmeldungen
            // mehrerer Batches zu einer zusammen.
            $this->notificationService->setNotificationKey(Str::random(15));
            $this->notificationService->setTitle($title);
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => $action === self::ACTION_DELETED ? 'error' : 'success',
                'message' => $title,
            ]);
            $this->notificationService->setDescription($this->description($events, $project, $action));
            $this->notificationService->setNotificationTo($recipient);
            $this->notificationService->createNotification();
        }
    }

    /**
     * Projektleitung, Ersteller der betroffenen Termine und in deren Schichten eingeteilte
     * Personen — ohne die Person, die den Assistenten bedient hat.
     *
     * Die Regel ist dem Loeschpfad des Bestands entnommen
     * (`EventService::createEventDeletedNotificationsForProjectManagers()` und
     * `createEventDeletedNotification()`); einen benachrichtigenden Bulk-Anlegepfad gibt es im
     * Repo nicht.
     *
     * @param Collection<int, Event> $events
     * @return Collection<int, User>
     */
    private function recipients(Collection $events, User $causer): Collection
    {
        $recipients = Collection::make();

        foreach ($events as $event) {
            $project = $event->project;

            if ($project instanceof Project) {
                $recipients = $recipients->merge($project->managerUsers);
            }

            if ($event->creator instanceof User) {
                $recipients->push($event->creator);
            }

            foreach ($event->shifts as $shift) {
                $recipients = $recipients->merge($shift->users);
            }
        }

        return $recipients
            ->filter(static fn ($user): bool => $user instanceof User && $user->id !== $causer->id)
            ->unique('id')
            ->values();
    }

    /**
     * Aufbau wie im Bestand: Projekt-Link, Zeitraum, Link in den Kalender (W4; W3-Log fuer
     * `dashboard.redirect-to-calendar`).
     *
     * @param Collection<int, Event> $events
     * @return array<int, array{type: string, title: string|null, href: string|null}>
     */
    private function description(Collection $events, ?Project $project, string $action): array
    {
        $description = [
            1 => [
                'type' => 'link',
                'title' => $project?->name ?? '',
                'href' => $project === null ? null : route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        ),
                    ]
                ),
            ],
            2 => [
                'type' => 'string',
                'title' => $this->period($events),
                'href' => null,
            ],
        ];

        $first = $events->first();

        // Nach einer Loeschung zeigt der Link ins Leere; nach einer Ruecknahme haengt es davon ab,
        // was zurueckgenommen wurde. In beiden Faellen bleibt es beim Projekt-Link.
        if ($first !== null && $action !== self::ACTION_DELETED && $action !== self::ACTION_REVERTED) {
            $description[3] = [
                'type' => 'link',
                'title' => $first->eventName ?? ($first->event_type?->name ?? ''),
                'href' => route('dashboard.redirect-to-calendar', $first->id),
            ];
        }

        return $description;
    }

    /**
     * @param Collection<int, Event> $events
     */
    private function period(Collection $events): string
    {
        $start = $events->min('start_time');
        $end = $events->max('end_time');

        if ($start === null || $end === null) {
            return '';
        }

        return Carbon::parse($start)->translatedFormat('d.m.Y H:i')
            . ' - ' . Carbon::parse($end)->translatedFormat('d.m.Y H:i');
    }
}
