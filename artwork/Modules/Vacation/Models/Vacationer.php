<?php

namespace Artwork\Modules\Vacation\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Vacationer
{
    public function vacations(): MorphMany;
}
