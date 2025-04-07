<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;

class EventVerificationService
{
    public function approveVerification(EventVerification $verification)
    {
        $verification->update(['status' => 'approved']);
        // Trigger Logik wenn Modus 'any' erfüllt ist
    }

    public function rejectVerification(EventVerification $verification, string $reason)
    {
        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    public function requestVerification(Event $event)
    {
        $eventType = $event->event_type;

        $verifiers = match ($eventType->verification_mode) {
            'specific' => collect([$eventType->specificVerifier]),
            'any', 'all' => $eventType->verifiers,
        };

        foreach ($verifiers as $verifier) {
            $event->verifications()->create([
                'verifier_type' => get_class($verifier),
                'verifier_id' => $verifier->id,
                'status' => 'pending',
            ]);
        }
    }
}