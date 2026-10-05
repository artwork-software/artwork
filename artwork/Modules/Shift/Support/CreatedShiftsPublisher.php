<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Illuminate\Support\Collection;

/**
 * Neu angelegte Schichten veröffentlichen: Broadcast an alle offenen Ansichten (abgesichert gegen
 * einen nicht erreichbaren WebSocket-Server) und derselbe Stand als Flash `shiftPlanUpdate` für die
 * anlegende Person — deren Tages-/Wochenansicht übernimmt ihn sofort, statt nur auf den WebSocket zu
 * warten (ohne Verbindung blieb die Zelle leer, erneutes Anlegen erzeugte Duplikate).
 */
final class CreatedShiftsPublisher
{
    public static function publish(Collection $shifts, bool $flashForCreatingView = true): void
    {
        if ($shifts->isEmpty()) {
            return;
        }

        $event = new MultiShiftCreateInShiftPlan($shifts);

        if ($flashForCreatingView) {
            session()->flash('shiftPlanUpdate', json_decode(json_encode($event->broadcastWith()), true));
        }

        SafeBroadcast::send($event);
    }
}
