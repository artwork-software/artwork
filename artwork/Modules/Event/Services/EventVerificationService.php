<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Event\Events\BroadcastToReloadEventVerificationRequests;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EventVerificationService
{

    public function __construct(
        private readonly NotificationService $notificationService
    ){
    }

    public function getAllByUser(User $user, int $paginate = 5, string $filterVerificationRequest = '') {
        return EventVerification::where(function ($query) use ($user) {
            $query->where('verifier_id', $user->id)
                ->orWhere('event_id', $user->id);
            })
            ->with(['event.room', 'event.event_type', 'event.project', 'verifier', 'requester'])
            ->when(!empty($filterVerificationRequest), function ($query) use ($filterVerificationRequest) {
                $query->where('status', $filterVerificationRequest);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);
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

        $events->each(function ($event) {
            $groupedByUuid = $event->verifications
                ->sortByDesc('created_at')
                ->groupBy('uuid');

            $latestGroup = $groupedByUuid->first();
            $totalCount = $latestGroup->count() ?: 1; // Vermeidet Division durch 0

            $groupedByStatus = collect(['approved', 'rejected', 'pending'])->mapWithKeys(function ($status) use ($latestGroup) {
                $verifiers = $latestGroup->where('status', $status)->map(function ($verification) use ($status) {
                    $verifier = $verification->verifier;

                    // Erstelle ein sauberes Objekt mit nur den nötigen Daten + rejection_reason
                    return (object) array_merge(
                        $verifier->only([
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
                        ['rejection_reason' => $status === 'rejected' ? $verification->rejection_reason : null]
                    );
                });

                return [$status => $verifiers->values()];
            });

            $statusCounts = collect(['pending', 'approved', 'rejected'])->mapWithKeys(function ($status) use ($latestGroup) {
                return [
                    $status => $latestGroup->where('status', $status)->count()
                ];
            });

            // Setze Farbe basierend auf Verifizierungsmodus und Status
            $eventType = $event->event_type;
            $verificationMode = $eventType->verification_mode;
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
            ->whereDoesntHave('verifications')
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
        $notificationTitle = '';

        switch ($eventType->verification_mode) {
            case 'any':
                $event->update(['is_planning' => false, 'occupancy_option' => false]);
                $notificationTitle = __('notification.request-verification.approved-finished', [], $eventCreator->language);
                $event->verifications()->where('status', 'pending')->delete();
                break;

            case 'all':
                $approvedCount = $event->verifications()->where('status', 'approved')->where('uuid', $verification->uuid )->count();
                $totalCount = $event->verifications()->whereIn('status', ['approved', 'rejected'])->where('uuid', $verification->uuid )->count();
                $notificationTitle = __('notification.request-verification.user-approved', [
                    'name' => $verification->verifier->full_name,
                ], $eventCreator->language);
                if ($approvedCount === $totalCount) {
                    $event->update(['is_planning' => false, 'occupancy_option' => false]);
                    $notificationTitle = __('notification.request-verification.approved-finished', [], $eventCreator->language);
                }
                break;

            case 'specific':
                $specificVerifier = $eventType->specificVerifier;
                if ($verification->verifier_id === $specificVerifier->id) {
                    $event->update(['is_planning' => false, 'occupancy_option' => false]);
                    $notificationTitle = __('notification.request-verification.approved-finished', [], $eventCreator->language);
                }
                break;
        }

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);

        $broadcastMessage = [
            'id' => random_int(1, 1000000),
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
        $verification->event->update(['is_planning' => true]);

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);
        $notificationTitle = __('notification.request-verification.user-rejected', [
            'name' => $verification->verifier->full_name,
        ], $eventCreator->language);
        $broadcastMessage = [
            'id' => random_int(1, 1000000),
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

        if ($eventType->verification_mode === 'none') {
            $event->update(['is_planning' => false, 'occupancy_option' => false]);
            return;
        }

        $verifiers = match ($eventType->verification_mode) {
            'specific' => collect([$eventType->specificVerifier]),
            'any', 'all' => $eventType->verifiers,
            'none' => collect([]),
        };

        if ($eventType->verification_mode === 'specific' && $eventType->specificVerifier->id === $user->id) {
            $event->verifications()->create([
                'uuid' => $uuid,
                'verifier_type' => get_class($user),
                'verifier_id' => $user->id,
                'status' => 'approved',
                'request_user_id' => $user->id,
            ]);
            $event->update(['is_planning' => false, 'occupancy_option' => false]);
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
            $event->update(['is_planning' => false, 'occupancy_option' => false]);
            return;
        }

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);

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
                    'id' => random_int(1, 1000000),
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

            broadcast(new BroadcastToReloadEventVerificationRequests($verifier));
        }
    }


    public function cancelVerification(Event $event): void
    {
        $event->update(['is_planning' => true]);
        foreach ($event->verifications as $verification) {
            $verifier = $verification->verifier;
            $verification->delete();
            broadcast(new BroadcastToReloadEventVerificationRequests($verifier));
        }
    }
}