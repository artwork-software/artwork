<?php

namespace Artwork\Modules\ProjectTab\Enums;

enum ProjectTabComponentPermissionEnum: string
{
    case PERMISSION_TYPE_ALL_SEE_AND_EDIT = 'allSeeAndEdit';

    case PERMISSION_TYPE_ALL_SEE_SOME_EDIT = 'allSeeSomeEdit';

    case PERMISSION_TYPE_SOME_SEE_SOME_EDIT = 'someSeeSomeEdit';
}
