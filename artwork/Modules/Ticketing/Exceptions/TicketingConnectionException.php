<?php

namespace Artwork\Modules\Ticketing\Exceptions;

use RuntimeException;

/** Der Handshake mit artwork tickets ist gescheitert; die Meldung ist für die Oberfläche gedacht. */
class TicketingConnectionException extends RuntimeException
{
}
