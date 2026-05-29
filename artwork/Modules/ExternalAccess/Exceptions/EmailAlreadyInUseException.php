<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class EmailAlreadyInUseException extends ExternalAccessException
{
    public static function forEmail(string $email): self
    {
        return new self('The proposed email already belongs to another external access.');
    }
}
