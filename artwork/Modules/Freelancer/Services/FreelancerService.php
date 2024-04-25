<?php

namespace Artwork\Modules\Freelancer\Services;

use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;

readonly class FreelancerService
{
    public function __construct(private FreelancerRepository $freelancerRepository)
    {
    }
}
