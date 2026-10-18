<?php

namespace Artwork\Modules\Ticketing\Exceptions;

use RuntimeException;

/** Ein Termin im Verkauf lässt sich im Kalender nicht löschen; zurückziehen geht nur in der Ticketing-Komponente. */
class TicketingLockedException extends RuntimeException
{
}
