<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EventVerificationService
{

    public function __construct(
        private readonly NotificationService $notificationService
    ){
    }

    public function getAllByUser(User $user, int $paginate = 5) {
        return EventVerification::where('verifier_id', $user->id)
            ->orWhere('event_id', $user->id)
            ->with(['event', 'verifier'])
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);
    }

    public function getAllByRequester(User $user, int $paginate = 5) {
        $events = Event::where('user_id', $user->id)
            ->whereHas('verifications')
            ->with(['event_type', 'verifications.verifier'])
            ->withCasts([
                'created_at' => TranslatedDateTimeCast::class,
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($paginate);

        // Gruppiere verifications innerhalb jedes Events nach UUID
        $events->each(function ($event) {
            $event->grouped_verifications = $event->verifications
                ->sortByDesc('created_at')
                ->groupBy('uuid')
                ->values();
            unset($event->verifications); // optional
        });

        return $events;
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
        $eventCreator = $verification->event->creator;
        if ($verification->event->event_type->verification_mode === 'any') {
            $verification->event->update(['is_planning' => false]);

            $notificationTitle = __(
                'notification.request-verification.approved-finished',
                [],
                $eventCreator->language
            );
            // remove all verifications where status is pending
            $verification->event->verifications()
                ->where('status', 'pending')
                ->delete();
        }
        if ($verification->event->event_type->verification_mode === 'all') {
            $allVerifications = $verification->event->verifications()->where('status', 'approved')->count();
            $totalVerifications = $verification->event->verifications()->count();

            $notificationTitle = __(
                'notification.request-verification.user-approved',
                [
                    'name' => $verification->verifier->full_name,
                ],
                $eventCreator->language
            );

            if ($allVerifications === $totalVerifications) {
                $verification->event->update(['is_planning' => false]);

                $notificationTitle = __(
                    'notification.request-verification.approved-finished',
                    [],
                    $eventCreator->language
                );
            }
        }

        if ($verification->event->event_type->verification_mode === 'specific') {
            $specificVerifier = $verification->event->event_type->specificVerifier;
            if ($verification->verifier_id === $specificVerifier->id) {
                $verification->event->update(['is_planning' => false]);
                // set all verifications to approved

                $notificationTitle = __(
                    'notification.request-verification.approved-finished',
                    [],
                    $eventCreator->language
                );
            }
        }

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);


        $broadcastMessage = [
            'id' => random_int(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            2 => [
                'type' => 'string',
                'title' =>  __(
                    'notification.request-verification.event-verification-description',
                    [
                        'event' => $verification->event->eventName,
                    ],
                    $eventCreator->language
                ),
                'href' => null,
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($eventCreator);
        $this->notificationService->createNotification();
    }

    public function rejectVerification(EventVerification $verification, string $reason): void
    {
        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    public function requestVerification(Event $event, User $user): void
    {
        $eventType = $event->event_type;
        $uuid = Str::uuid()->toString();
        if($eventType->verification_mode === 'none') {
            $event->update(['is_planning' => false]);
            return;
        }

        if($event->user_id === $user->id) {
            $event->update(['is_planning' => false]);
        }

        $verifiers = match ($eventType->verification_mode) {
            'specific' => collect([$eventType->specificVerifier]),
            'any', 'all' => $eventType->verifiers,
            'none' => collect([]),
        };

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS);

        foreach ($verifiers as $verifier) {
            $event->verifications()->create([
                'uuid' => $uuid,
                'verifier_type' => get_class($verifier),
                'verifier_id' => $verifier->id,
                'status' => 'pending',
                'request_user_id' => $user->id,
            ]);

            $notificationTitle = __(
                'notification.request-verification.new',
                [],
                $verifier->language
            );
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                2 => [
                    'type' => 'link',
                    'title' =>  __(
                        'notification.request-verification.open-index',
                        [],
                        $verifier->language
                    ),
                    'href' => route('event-verifications.index'),
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($verifier);
            $this->notificationService->createNotification();
        }
    }
}