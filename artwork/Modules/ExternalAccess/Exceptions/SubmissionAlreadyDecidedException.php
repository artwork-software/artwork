<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class SubmissionAlreadyDecidedException extends ExternalAccessException
{
    public static function make(): self
    {
        return new self('This submission has already been reviewed.');
    }
}
