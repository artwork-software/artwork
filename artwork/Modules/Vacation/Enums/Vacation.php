<?php

namespace Artwork\Modules\Vacation\Enums;

enum Vacation: string
{
    case OFF_WORK = 'OFF_WORK';
    case NOT_AVAILABLE = 'NOT_AVAILABLE';
    case AVAILABLE = 'AVAILABLE';
}
