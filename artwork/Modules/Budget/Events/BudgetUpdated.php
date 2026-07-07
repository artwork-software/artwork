<?php

namespace Artwork\Modules\Budget\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BudgetUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly int $projectId
    ) {
    }
}
