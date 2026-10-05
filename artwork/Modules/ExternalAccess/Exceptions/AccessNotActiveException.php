<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;

class AccessNotActiveException extends ExternalAccessException
{
    public static function forAccess(ExternalAccess $access): self
    {
        return new self(
            'This access has no active CRM or tab access; extend it before resending the invitation.'
        );
    }
}
