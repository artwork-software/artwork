<?php

namespace Artwork\Modules\Event\Services;

use App\Settings\EventSettings;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Schaltet "Termine immer direkt buchbar" um. Beim Einschalten wird der Altbestand übernommen:
 * offene Raumbelegungsanfragen gelten als angenommen, offene Verifizierungsanfragen als genehmigt
 * (die geplanten Termine werden feste Termine). Das Frontend warnt vorher mit den Zählern.
 */
class DirectBookingActivationService
{
    public function __construct(
        private readonly EventSettings $eventSettings,
        private readonly EventVerificationService $eventVerificationService,
        private readonly ChangeService $changeService,
    ) {
    }

    public function openRoomRequestsCount(): int
    {
        return $this->openRoomRequestsQuery()->count();
    }

    public function pendingVerificationsCount(): int
    {
        return EventVerification::where('status', 'pending')
            ->whereHas('event')
            ->distinct('event_id')
            ->count('event_id');
    }

    /**
     * @return array{accepted_room_requests: int, approved_verifications: int}
     */
    public function apply(bool $enabled): array
    {
        $result = ['accepted_room_requests' => 0, 'approved_verifications' => 0];
        $wasEnabled = (bool) ($this->eventSettings->always_direct_booking ?? false);

        $this->eventSettings->always_direct_booking = $enabled;
        $this->eventSettings->save();

        if ($enabled && !$wasEnabled) {
            $result['accepted_room_requests'] = $this->acceptOpenRoomRequests();
            $result['approved_verifications'] = $this->eventVerificationService->approveAllPending();
        }

        $this->forgetMenuCaches();

        return $result;
    }

    private function acceptOpenRoomRequests(): int
    {
        $events = $this->openRoomRequestsQuery()->get();

        foreach ($events as $event) {
            // Spiegelt EventController::acceptEvent ohne Benachrichtigungen: die Person wird
            // über die Warnung beim Aktivieren informiert, nicht pro Termin.
            Event::query()->whereKey($event->id)->update(['occupancy_option' => false]);
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($event->id)
                    ->setTranslationKey('Room confirmed')
            );
        }

        return $events->count();
    }

    private function openRoomRequestsQuery()
    {
        return Event::query()
            ->where('occupancy_option', true)
            ->whereNotNull('room_id');
    }

    /** HandleInertiaRequests cacht "Eingehende Anfragen sichtbar" 5 Minuten pro Person. */
    private function forgetMenuCaches(): void
    {
        User::query()->pluck('id')->each(
            static fn (int $id) => Cache::forget("user:{$id}:can_see_incoming_requests")
        );
    }
}
