<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class InternalUserEmailConflictException extends ExternalAccessException
{
    public static function forEmail(string $email): self
    {
        return new self('This email belongs to an internal user account.');
    }
}
