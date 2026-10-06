<?php

namespace Artwork\Modules\Ticketing\Exceptions;

use RuntimeException;
use Throwable;

/** Der Handshake mit artwork tickets ist gescheitert; die Meldung ist für die Oberfläche gedacht. */
class TicketingConnectionException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $transient = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** tickets war nicht erreichbar oder hat selbst einen Fehler geworfen: ein späterer Versuch kann klappen. */
    public static function transient(string $message, ?Throwable $previous = null): self
    {
        return new self($message, true, $previous);
    }

    public function isTransient(): bool
    {
        return $this->transient;
    }
}
