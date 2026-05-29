<?php

namespace Artwork\Modules\ExternalAccess\Enums;

enum SelfEditSectionMode: string
{
    case STAGED = 'staged';   // writes go through pending submission + approval
    case DIRECT = 'direct';   // writes hit CrmPropertyValue immediately
}
