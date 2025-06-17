<?php

namespace Artwork\Modules\Inventory\Enums;

enum InventoryPropertyTypeEnum: string
{
    case TEXT = 'text';
    case DATE = 'date';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case NUMBER = 'number';
    case FILE = 'file';
}
