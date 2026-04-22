<?php

namespace Artwork\Modules\BusinessIntelligence\Enums;

enum BiEffortBucketEnum: string
{
    case BUCKET_0_10 = '0-10';
    case BUCKET_10_25 = '10-25';
    case BUCKET_25_50 = '25-50';
    case BUCKET_50_100 = '50-100';
    case BUCKET_100_PLUS = '100+';
}
