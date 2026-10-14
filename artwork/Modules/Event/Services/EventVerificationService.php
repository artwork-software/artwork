<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Events\BroadcastToReloadEventVerificationRequests;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Artwork\Modules\Shift\Support\SafeBroadcast;

class EventVerificationService
{

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly EventSettingsService $eventSettingsService,
    ) {
    }

    /**
     * Stellt einen geplanten Termin auf einen "richtigen" Termin um. Geplante Termine kennen keine
     * Raumanfrage (seit 13.10.2026), der bestätigte Termin ist fest gebucht.
     */
    private function confirmEvent(Event $event): void
    {
        $this->trackIsPlanningChange($event, false);
        $event->update(['is_planning' => false, 'occupancy_option' => false]);
    }

    /**
     * Macht aus einem Termin einen geplanten Termin. Geplante Termine kennen keine Raumanfrage: eine offene
     * Anfrage wird zurückgenommen (sonst blieb sie schraffiert im Planungskalender stehen, ohne dass ein
     * Raumadmin sie noch beantworten konnte – EventPolicy::answerRoomRequest schließt geplante Termine aus).
     */
    public function convertToPlanning(Event $event): void
    {
        $this->trackIsPlanningChange($event, true);

        if ($event->occupancy_option) {
            $this->notificationService->deleteUnhandledRoomRequestNotificationsByEventId($event->id);
        }

        $event->update(['is_planning' => true, 'occupancy_option' => false]);
    }

    /**
     * Eine Prüfung kann nur für geplante Termine ohne offene Prüfrunde angefragt werden. Für feste Termine
     * hätte requestVerification() über confirmEvent() eine offene Raumanfrage ohne Raumadmin fest gebucht;
     * eine zweite Anfrage während einer offenen Runde legte die Prüfenden doppelt an.
     */
    public function canRequestVerification(Event $event): bool
    {
        return (bool) $event->is_planning
            && !$event->verifications()->where('status', 'pending')->exists();
    }

    /**
     * Prüfung verlangt, aber niemand hinterlegt (keine Prüfenden, spezifische Person gelöscht): eine Anfrage
     * legte keine einzige Prüfung an – der Termin blieb unverändert, ohne Rückmeldung.
     */
    public function lacksVerifiers(Event $event): bool
    {
        $eventType = $event->event_type;
        if (
            $eventType === null
            || $eventType->verification_mode === 'none'
            || $this->eventSettingsService->alwaysDirectBooking()
        ) {
            return false;
        }

        return $this->verifiersOf($eventType)->isEmpty();
    }

    /**
     * @return Collection<int, User>
     */
    private function verifiersOf(EventType $eventType): Collection
    {
        return match ($eventType->verification_mode) {
            // filter(): gelöschter spezifischer Verifizierer darf nicht als null-Eintrag landen
            'specific' => collect([$eventType->specificVerifier])->filter(),
            'any', 'all' => $eventType->verifiers,
            default => collect(),
        };
    }

    /**
     * Altbestand beim Aktivieren von "Termine immer direkt buchbar": alle offenen Verifizierungsanfragen
     * gelten als genehmigt, die betroffenen geplanten Termine werden zu festen Terminen.
     *
     * @return int Anzahl übernommener Termine
     */
    public function approveAllPending(): int
    {
        $eventIds = EventVerification::where('status', 'pending')
            ->whereHas('event')
            ->pluck('event_id')
            ->unique();

        $count = 0;
        foreach (Event::whereIn('id', $eventIds)->get() as $event) {
            $event->verifications()->where('status', 'pending')->update(['status' => 'approved']);
            $this->confirmEvent($event);
            $count++;
        }

        return $count;
    }

    private function trackIsPlanningChange(Event $event, bool $newIsPlanning): void
    {
        if ((bool) $event->is_planning === $newIsPlanning) {
            return;
        }

        $translationKey = $newIsPlanning
            ? 'Event converted to planning event'
            : 'Event confirmed';

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Event::class)
                ->setModelId($event->id)
                ->setTranslationKey($translationKey)
        );
    }

    public function getAllRequestedByUser(User $user, int $paginate = 5)
    {
        $events = Event::where('user_id', $user->id)
            ->whereHas('verifications')
            ->with(['event_type', 'project', 'room', 'verifications.verifier'])
            ->withCasts([
                'created_at' => TranslatedDateTimeCast::class,
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);

        $events->each(function ($event): void {
            // Nach id statt created_at: created_at ist per TranslatedDateTimeCast ein übersetzter String
            // ("Mittwoch, 14. Oktober …") und sortierte nach Wochentag – die aktuelle Prüfrunde war Zufall
            $groupedByUuid = $event->verifications
                ->sortByDesc('id')
                ->groupBy('uuid');

            $latestGroup = $groupedByUuid->first();
            $totalCount = $latestGroup->count() ?: 1; // Vermeidet Division durch 0

            $groupedByStatus = collect([
                'approved',
                'rejected',
                'pending',
            ])->mapWithKeys(function ($status) use ($latestGroup) {
                // Gelöschte Prüfende (verifier = null) fehlen in der Personenliste, zählen aber im Status mit
                $verificationsWithVerifier = $latestGroup->where('status', $status)
                    ->filter(fn ($verification) => $verification->verifier !== null);
                $verifiers = $verificationsWithVerifier->map(function ($verification) use ($status) {
                    $verifier = $verification->verifier;

                    // Erstelle ein sauberes Objekt mit nur den nötigen Daten + rejection_reason
                    $viewer = Auth::user();

                    return (object) array_merge(
                        $verifier->only([
                            // id: Schlüssel der Avatar-Liste im Frontend (fehlte → doppelte undefined-Keys)
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'phone_number',
                            'position',
                            'pronouns',
                            'description',
                            'email_private',
                            'phone_private',
                            'profile_photo_url',
                            'full_name',
                        ]), // erweitere hier bei Bedarf
                        [
                            'email' => $verifier->visibleEmailFor($viewer),
                            'phone_number' => $verifier->visiblePhoneNumberFor($viewer),
                        ],
                        ['rejection_reason' => $status === 'rejected' ? $verification->rejection_reason : null]
                    );
                });

                return [$status => $verifiers->values()];
            });

            $statusCounts = collect([
                'pending',
                'approved',
                'rejected',
            ])->mapWithKeys(function ($status) use ($latestGroup) {
                return [
                    $status => $latestGroup->where('status', $status)->count()
                ];
            });

            // Setze Farbe basierend auf Verifizierungsmodus und Status
            $eventType = $event->event_type;
            $verificationMode = $eventType?->verification_mode;
            $statusColor = 'gray';

            if ($statusCounts['rejected'] > 0) {
                $statusColor = 'red'; // Sofort rot bei Ablehnung
            } elseif ($verificationMode === 'all') {
                $statusColor = $statusCounts['pending'] === 0 ? 'green' : 'yellow';
            } elseif ($verificationMode === 'any') {
                $statusColor = $statusCounts['approved'] > 0 ? 'green' : 'yellow';
            } elseif ($verificationMode === 'specific') {
                $statusColor = $statusCounts['approved'] > 0 ? 'green' : 'yellow';
            } elseif ($verificationMode === 'none') {
                $statusColor = 'green';
            }

            $event->status_color = $statusColor;

            $statusPercentages = $statusCounts->map(function ($count) use ($totalCount) {
                return round(($count / $totalCount) * 100, 2);
            });

            $event->verifier_grouped_by_status = $groupedByStatus;
            $event->verification_status_counts = $statusCounts->merge([
                'total' => $latestGroup->count(),
            ]);
            $event->verification_status_percentages = $statusPercentages;

            unset($event->verifications);
        });

        return $events;
    }


    public function getPlannedEvents(User $user): Collection
    {
        return Event::where('user_id', $user->id)
            ->where('is_planning', true)
            ->with(['room', 'event_type', 'project'])
            ->orderBy('created_at', 'desc')
            // Nach Ablehnung/Zurückziehen ist der Termin wieder nur geplant und muss hier erneut anfragbar sein –
            // vorher verschwand er nach der ersten Ablehnung aus "Geplante Termine"
            ->whereDoesntHave('verifications', fn ($query) => $query->where('status', 'pending'))
            ->get();
    }

    public function getCountsByUser(User $user): array
    {
        $requests = Event::where('user_id', $user->id)
            ->where('is_planning', true)
            ->count();

        $verifications = EventVerification::where('verifier_id', $user->id)
            ->select('status')
            ->get()
            ->groupBy('status')
            ->map->count();

        return [
            'requests' => $requests,
            'approved' => $verifications['approved'] ?? 0,
            'pending' => $verifications['pending'] ?? 0,
            'rejected' => $verifications['rejected'] ?? 0,
        ];
    }

    public function approveVerification(EventVerification $verification): void
    {
        $verification->update(['status' => 'approved']);

        $event = $verification->event;
        $eventType = $event->event_type;
        $eventCreator = $event->creator;
        // Ohne vollständige Freigabe (Modus "spezifisch" durch andere Person, Typ ohne Modus) nicht mit leerem
        // Titel benachrichtigen
        $notificationTitle = __('notification.request-verification.user-approved', [
            'name' => $verification->verifier?->full_name ?? '',
        ], $eventCreator?->language);

        switch ($eventType?->verification_mode) {
            case 'any':
                $this->confirmEvent($event);
                $notificationTitle = __(
                    'notification.request-verification.approved-finished',
                    [],
                    $eventCreator?->language
                );
                // Die übrigen offenen Prüfungen erledigen sich – deren Eingangslisten neu laden
                $obsoleteVerifications = $event->verifications()->where('status', 'pending')->with('verifier')->get();
                $event->verifications()->where('status', 'pending')->delete();
                foreach ($obsoleteVerifications as $obsoleteVerification) {
                    $this->broadcastReload($obsoleteVerification->verifier);
                }
                break;

            case 'all':
                $approvedCount = $event->verifications()->where('status', 'approved')->where(
                    'uuid',
                    $verification->uuid
                )->count();
                // Alle Prüfenden der Anfrage zählen – auch die noch offenen. Ohne 'pending' galt bei
                // drei Prüfenden schon die erste Zustimmung als „vollständig freigegeben“.
                $totalCount = $event->verifications()->whereIn('status', [
                    'approved',
                    'rejected',
                    'pending',
                ])->where('uuid', $verification->uuid)->count();
                $notificationTitle = __('notification.request-verification.user-approved', [
                    'name' => $verification->verifier?->full_name ?? '',
                ], $eventCreator?->language);
                if ($approvedCount === $totalCount) {
                    $this->confirmEvent($event);
                    $notificationTitle = __(
                        'notification.request-verification.approved-finished',
                        [],
                        $eventCreator?->language
                    );
                }
                break;

            case 'specific':
                $specificVerifier = $eventType->specificVerifier;
                if ($specificVerifier !== null && $verification->verifier_id === $specificVerifier->id) {
                    $this->confirmEvent($event);
                    $notificationTitle = __(
                        'notification.request-verification.approved-finished',
                        [],
                        $eventCreator?->language
                    );
                }
                break;
        }

        // Ersteller gelöscht – Status ist gesetzt, aber es gibt keinen Benachrichtigungs-Empfänger mehr
        if ($eventCreator === null) {
            return;
        }

        // "Gesendet"-Ansicht der anfragenden Person live aktualisieren
        $this->broadcastReload($eventCreator);

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this
            ->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);

        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ];

        $notificationDescription = [
            2 => [
                'type' => 'string',
                'title' => __('notification.request-verification.event-verification-description', [
                    'event' => $event->eventName,
                ], $eventCreator->language),
                'href' => null,
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($eventCreator);
        $this->notificationService->createNotification();
    }

    public function approveVerificationByEvent(Event $event, User $user): void
    {
        /** @var EventVerification $verification */
        $verification = $event->verifications()
            ->where('verifier_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$verification) {
            return;
        }

        $this->approveVerification($verification);
    }

    public function rejectVerificationByEvent(Event $event, User $user, string $rejectionReason = ''): void
    {
        /** @var EventVerification $verification */
        $verification = $event->verifications()
            ->where('verifier_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$verification) {
            return;
        }

        $this->rejectVerification($verification, $rejectionReason);
    }



    public function rejectVerification(EventVerification $verification, ?string $reason = ''): void
    {
        $event = $verification->event;
        $eventCreator = $event->creator;
        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
        $this->trackIsPlanningChange($event, true);
        $verification->event->update(['is_planning' => true]);

        // Modus "alle": eine Ablehnung entscheidet die Runde (Entscheidung 14.10.2026). Die übrigen offenen
        // Prüfungen werden geschlossen – vorher prüften die anderen umsonst weiter und der Termin zeigte
        // weiter "Prüfung angefragt".
        if ($event->event_type?->verification_mode === 'all') {
            $this->closeOpenVerificationsOfRound($event, $verification->uuid);
        }

        // Ersteller gelöscht – Status ist gesetzt, aber es gibt keinen Benachrichtigungs-Empfänger mehr
        if ($eventCreator === null) {
            return;
        }

        $this->broadcastReload($eventCreator);

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(3);
        $this
            ->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);
        $notificationTitle = __('notification.request-verification.user-rejected', [
            'name' => $verification->verifier?->full_name ?? '',
        ], $eventCreator->language);
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            2 => [
                'type' => 'string',
                'title' => __('notification.request-verification.event-verification-description', [
                    'event' => $event->eventName,
                ], $eventCreator->language),
                'href' => null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($eventCreator);
        $this->notificationService->createNotification();
    }

    public function requestVerification(Event $event, User $user): void
    {
        $eventType = $event->event_type;
        $uuid = Str::uuid()->toString();

        // Ohne Event-Typ gibt es keinen Verifizierungsmodus – wie 'none' behandeln.
        // "Termine immer direkt buchbar" schaltet die Verifizierung instanzweit ab: sofort übernehmen.
        if (
            $eventType === null
            || $eventType->verification_mode === 'none'
            || $this->eventSettingsService->alwaysDirectBooking()
        ) {
            $this->confirmEvent($event);
            return;
        }

        $verifiers = $this->verifiersOf($eventType);

        if ($eventType->verification_mode === 'specific' && $eventType->specificVerifier?->id === $user->id) {
            $event->verifications()->create([
                'uuid' => $uuid,
                'verifier_type' => get_class($user),
                'verifier_id' => $user->id,
                'status' => 'approved',
                'request_user_id' => $user->id,
            ]);
            $this->confirmEvent($event);
            return;
        }

        if ($eventType->verification_mode === 'any' && $verifiers->contains('id', $user->id)) {
            $event->verifications()->create([
                'uuid' => $uuid,
                'verifier_type' => get_class($user),
                'verifier_id' => $user->id,
                'status' => 'approved',
                'request_user_id' => $user->id,
            ]);
            $this->confirmEvent($event);
            return;
        }

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this
            ->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);

        /** @var User $verifier */
        foreach ($verifiers as $verifier) {
            $status = 'pending';

            // Bei ALL direkt genehmigen wenn User beteiligt ist
            if ($eventType->verification_mode === 'all' && $verifier->id === $user->id) {
                $status = 'approved';
            }

            $event->verifications()->create([
                'uuid' => $uuid,
                'verifier_type' => get_class($verifier),
                'verifier_id' => $verifier->id,
                'status' => $status,
                'request_user_id' => $user->id,
            ]);

            if ($status === 'pending') {
                $notificationTitle = __('notification.request-verification.new', [], $verifier->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle,
                ];
                $notificationDescription = [
                    2 => [
                        'type' => 'link',
                        'title' => __('notification.request-verification.open-index', [], $verifier->language),
                        'href' => route('event-verifications.index'),
                    ],
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($verifier);
                $this->notificationService->createNotification();
            }

            $this->broadcastReload($verifier);
        }
    }


    public function cancelVerification(Event $event): void
    {
        $this->trackIsPlanningChange($event, true);
        $event->update(['is_planning' => true]);
        $this->notificationService->deleteUnhandledRoomRequestNotificationsByEventId($event->id);

        // Nur die offene Prüfrunde zurückziehen (Entscheidung 14.10.2026) – frühere Runden bleiben als Verlauf
        // erhalten; vorher wurden alle Prüfungen des Termins gelöscht
        $openRoundUuids = $event->verifications()->where('status', 'pending')->pluck('uuid')->unique();
        $withdrawnVerifications = $event->verifications()
            ->whereIn('uuid', $openRoundUuids)
            ->with('verifier')
            ->get();
        foreach ($withdrawnVerifications as $verification) {
            $verifier = $verification->verifier;
            $verification->delete();
            $this->broadcastReload($verifier);
        }
    }

    /**
     * Schließt die noch offenen Prüfungen einer Runde (z. B. nach einer Ablehnung im Modus "alle") und lädt die
     * Eingangslisten der betroffenen Prüfenden neu.
     */
    private function closeOpenVerificationsOfRound(Event $event, ?string $uuid): void
    {
        $openVerifications = $event->verifications()
            ->where('uuid', $uuid)
            ->where('status', 'pending')
            ->with('verifier')
            ->get();
        foreach ($openVerifications as $openVerification) {
            $verifier = $openVerification->verifier;
            $openVerification->delete();
            $this->broadcastReload($verifier);
        }
    }

    /**
     * Lädt Eingangs- bzw. "Gesendet"-Liste der Person neu. Gelöschte Prüfende (verifier = null): nichts zu
     * aktualisieren – der Broadcast verlangt einen User (vorher TypeError beim Zurückziehen).
     */
    private function broadcastReload(mixed $user): void
    {
        if ($user instanceof User) {
            SafeBroadcast::send(new BroadcastToReloadEventVerificationRequests($user));
        }
    }
}
