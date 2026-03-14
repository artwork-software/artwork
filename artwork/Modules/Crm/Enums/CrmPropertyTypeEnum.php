<?php

namespace Artwork\Modules\Crm\Enums;

enum CrmPropertyTypeEnum: string
{
    case TEXT = 'text';
    case DATE = 'date';
    case NUMBER = 'number';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case LINK = 'link';
    case TEXTAREA = 'textarea';
    case UPLOAD = 'upload';
}
