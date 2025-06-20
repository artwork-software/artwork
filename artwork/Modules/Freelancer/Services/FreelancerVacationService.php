<?php

namespace Artwork\Modules\Freelancer\Services;

use Artwork\Modules\Freelancer\Repositories\FreelancerVacationRepository;

readonly class FreelancerVacationService
{
    public function __construct(private FreelancerVacationRepository $freelancerVacationRepository)
    {
    }
}
