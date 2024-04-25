<?php

namespace Artwork\Modules\Role\Enums;

enum RoleEnum: string
{
    case ARTWORK_ADMIN = 'artwork admin';

    case BUDGET_ADMIN = 'budget admin';

    case CONTRACT_ADMIN = 'contract admin';

    case MONEY_SOURCE_ADMIN = 'money source admin';

    case ROOM_ADMIN = 'room admin';

    case USER = 'user';
}
