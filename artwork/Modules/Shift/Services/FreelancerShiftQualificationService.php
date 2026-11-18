<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Repositories\FreelancerShiftQualificationRepository;

readonly class FreelancerShiftQualificationService
{
    public function __construct(private FreelancerShiftQualificationRepository $freelancerShiftQualificationRepository)
    {
    }
}
