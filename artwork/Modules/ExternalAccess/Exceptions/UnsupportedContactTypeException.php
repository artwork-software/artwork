<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class UnsupportedContactTypeException extends ExternalAccessException
{
    public static function forSlug(string $slug): self
    {
        $exception = new self('The selected contact type is not invitable for external access.');
        $exception->slug = $slug;

        return $exception;
    }

    private string $slug = '';

    public function slug(): string
    {
        return $this->slug;
    }
}
