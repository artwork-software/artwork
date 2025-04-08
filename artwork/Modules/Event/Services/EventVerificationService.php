<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\User\Models\User;

class EventVerificationService
{

    public function getAllByUser(User $user) {
        return EventVerification::where('verifier_id', $user->id)
            ->orWhere('event_id', $user->id)
            ->with(['event', 'verifier'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAllByRequester(User $user) {
        return Event::where('user_id', $user->id)
            ->whereHas('verifications')
            ->with(['event_type', 'verifications' => function ($query) {
                $query->with('verifier');
            }])
            ->withCasts([
                'created_at' => TranslatedDateTimeCast::class,
            ])
            ->orderBy('created_at', 'desc')
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

        if ($verification->event->event_type->verification_mode === 'any') {
            $verification->event->update(['is_planning' => false]);
            // set all verifications to approved
            $verification->event->verifications()->where('status', 'pending')->update(['status' => 'approved']);
        }
        if ($verification->event->event_type->verification_mode === 'all') {
            $allVerifications = $verification->event->verifications()->where('status', 'approved')->count();
            $totalVerifications = $verification->event->verifications()->count();

            if ($allVerifications === $totalVerifications) {
                $verification->event->update(['is_planning' => false]);
                // set all verifications to approved
                $verification->event->verifications()->where('status', 'pending')->update(['status' => 'approved']);
            }
        }

        if ($verification->event->event_type->verification_mode === 'specific') {
            $specificVerifier = $verification->event->event_type->specificVerifier;
            if ($verification->verifier_id === $specificVerifier->id) {
                $verification->event->update(['is_planning' => false]);

                // set all verifications to approved
                $verification->event->verifications()->where('status', 'pending')->update(['status' => 'approved']);
            }
        }
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

        if($eventType->verification_mode === 'none') {
            $event->update(['is_planning' => false]);
            return;
        }

        $verifiers = match ($eventType->verification_mode) {
            'specific' => collect([$eventType->specificVerifier]),
            'any', 'all' => $eventType->verifiers,
            'none' => collect([]),
        };

        foreach ($verifiers as $verifier) {
            $event->verifications()->create([
                'verifier_type' => get_class($verifier),
                'verifier_id' => $verifier->id,
                'status' => 'pending',
                'request_user_id' => $user->id,
            ]);
        }
    }
}