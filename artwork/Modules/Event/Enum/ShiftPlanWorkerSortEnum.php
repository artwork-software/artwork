<?php

namespace Artwork\Modules\Event\Enum;

enum ShiftPlanWorkerSortEnum: string
{
    case INTERN_EXTERN_ASCENDING = 'INTERN_EXTERN_ASCENDING';

    case INTERN_EXTERN_DESCENDING = 'INTERN_EXTERN_DESCENDING';

    case ALPHABETICALLY_NAME_ASCENDING = 'ALPHABETICALLY_NAME_ASCENDING';

    case ALPHABETICALLY_NAME_DESCENDING = 'ALPHABETICALLY_NAME_DESCENDING';
}
