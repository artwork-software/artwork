<?php

namespace Artwork\Modules\InventoryManagement\Enums;

enum CraftsInventoryColumnTypeEnum: int
{
    case TEXT = 0;
    case DATE = 1;
    case CHECKBOX = 2;
    case SELECT = 3;
}
