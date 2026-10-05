<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class EmailLinkedToOtherContactException extends ExternalAccessException
{
    public static function forEmail(string $email): self
    {
        return new self(
            'This email address already has an external access linked to a different CRM contact. '
            . 'Re-link that access or use another address.'
        );
    }
}
