<?php

namespace Artwork\Modules\ExternalAccess\Enums;

enum FieldApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
