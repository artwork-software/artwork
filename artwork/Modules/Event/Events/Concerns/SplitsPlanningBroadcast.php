<?php

namespace Artwork\Modules\Event\Events\Concerns;

use Artwork\Modules\Event\Models\Event;

/**
 * Geplante Termine liegen hinter dem Planungskalender-Recht (EventPolicy::viewPlanning). Die Termin-Kanäle
 * (event.room.*, bulk.events) darf jede angemeldete Person abonnieren – dort geht für geplante Termine nur
 * ein Hinweis ohne Inhalt raus ({id, …, redacted: true}), damit Ansichten ohne Recht die Kachel entfernen.
 * Den vollständigen Termin sendet ein zweiter Broadcast (planningCompanion) auf den Planungskanal, den
 * routes/channels.php nur mit Planungskalender-Recht freigibt. SafeBroadcast verschickt beide.
 *
 * @property Event $event
 */
trait SplitsPlanningBroadcast
{
    /** true = Instanz für den Planungskanal (vollständige Nutzlast) */
    public bool $onPlanningChannel = false;

    public function planningCompanion(): ?static
    {
        if ($this->onPlanningChannel || !$this->event->is_planning) {
            return null;
        }

        $companion = clone $this;
        $companion->onPlanningChannel = true;

        return $companion;
    }

    protected function sendsRedactedPayload(): bool
    {
        return (bool) $this->event->is_planning && !$this->onPlanningChannel;
    }
}
