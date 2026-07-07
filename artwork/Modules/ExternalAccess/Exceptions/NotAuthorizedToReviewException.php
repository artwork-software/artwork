<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class NotAuthorizedToReviewException extends ExternalAccessException
{
    public static function make(): self
    {
        return new self('You are not allowed to review this submission.');
    }
}
