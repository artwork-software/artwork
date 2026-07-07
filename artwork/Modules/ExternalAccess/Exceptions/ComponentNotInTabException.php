<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectTab;

class ComponentNotInTabException extends ExternalAccessException
{
    public function __construct(Component $component, ProjectTab $tab)
    {
        parent::__construct(
            sprintf('Component %d does not belong to tab %d.', $component->id, $tab->id),
        );
    }
}
