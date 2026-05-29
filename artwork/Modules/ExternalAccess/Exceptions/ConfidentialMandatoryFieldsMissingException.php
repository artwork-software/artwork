<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

class ConfidentialMandatoryFieldsMissingException extends ExternalAccessException
{
    /** @var list<string> */
    private array $missingFields = [];

    /**
     * @param list<string> $missingFields
     */
    public static function forFields(array $missingFields): self
    {
        $exception = new self(
            'The following confidential mandatory fields must be provided by the inviting user: '
            . implode(', ', $missingFields)
        );
        $exception->missingFields = array_values($missingFields);

        return $exception;
    }

    /**
     * @return list<string>
     */
    public function missingFields(): array
    {
        return $this->missingFields;
    }
}
