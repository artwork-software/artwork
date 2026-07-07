<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

use Artwork\Modules\Project\Models\Component;

class ComponentNotExternallyWritableException extends ExternalAccessException
{
    public function __construct(Component $component)
    {
        parent::__construct(
            sprintf('Component %d (type %s) is not externally writable.', $component->id, (string) $component->type),
        );
    }
}
