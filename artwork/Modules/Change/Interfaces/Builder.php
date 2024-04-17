<?php

namespace Artwork\Modules\Change\Interfaces;

use Antonrom\ModelChangesHistory\Models\Change;

interface Builder
{
    public function build(): Change;
}
