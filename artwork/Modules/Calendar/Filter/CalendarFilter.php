<?php

namespace Artwork\Modules\Calendar\Filter;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class CalendarFilter extends Model
{
    use HasFactory;
    use BelongsToUser;
}
