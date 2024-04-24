<?php

namespace Artwork\Modules\FreelancerVacation\Services;

use Artwork\Modules\FreelancerVacation\Repositories\FreelancerVacationRepository;

readonly class FreelancerVacationService
{
    public function __construct(private FreelancerVacationRepository $freelancerVacationRepository)
    {
    }
}
