<?php

namespace Artwork\Modules\Crm\Enums;

enum CrmSystemContactTypeEnum: string
{
    case FREELANCER = 'freelancer';
    case SERVICE_PROVIDER = 'service_provider';
    case MANUFACTURER = 'manufacturer';
    case ACCOMMODATION = 'accommodation';
    case ARTIST = 'artist';
    case USER = 'user';
}
