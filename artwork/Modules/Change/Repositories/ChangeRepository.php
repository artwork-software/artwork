<?php

namespace Artwork\Modules\Change\Repositories;

use Antonrom\ModelChangesHistory\Models\Change;

readonly class ChangeRepository
{
    public function save(Change $change): Change
    {
        $change->save();

        return $change;
    }
}
