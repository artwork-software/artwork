<?php

namespace Artwork\Modules\Event\Events\Concerns;

/**
 * Broadcast, der für geplante Termine zusätzlich auf dem Planungskanal gesendet wird (siehe
 * SplitsPlanningBroadcast); SafeBroadcast verschickt den Begleiter direkt nach dem Original.
 */
interface HasPlanningCompanionBroadcast
{
    public function planningCompanion(): ?object;
}
