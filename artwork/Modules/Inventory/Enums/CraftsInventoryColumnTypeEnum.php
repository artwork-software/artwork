<?php

namespace Artwork\Modules\Inventory\Enums;

enum CraftsInventoryColumnTypeEnum: int
{
    case TEXT = 0;
    case DATE = 1;
    case CHECKBOX = 2;
    case SELECT = 3;
    case NUMBER = 4;
    case UPLOAD = 5;

    case LAST_EDIT_AND_EDITOR = 99;
}
