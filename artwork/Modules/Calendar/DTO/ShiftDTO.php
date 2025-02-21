<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Shift\Models\Shift;
use Spatie\LaravelData\Data;

class ShiftDTO extends Data
{
    public function __construct()
    {
    }


    public static function fromModel(Shift $shift): ShiftDTO
    {
        return new self();
    }
}