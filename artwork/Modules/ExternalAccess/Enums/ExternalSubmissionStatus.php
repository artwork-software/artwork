<?php

namespace Artwork\Modules\ExternalAccess\Enums;

enum ExternalSubmissionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PARTIALLY_APPROVED = 'partially_approved';
    case SUPERSEDED = 'superseded';
}
