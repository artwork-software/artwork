<?php

namespace Artwork\Modules\ExternalAccess\Enums;

enum ExternalAccessType: string
{
    case READ = 'read';
    case WRITE = 'write';
}
