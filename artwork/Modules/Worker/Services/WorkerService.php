<?php

namespace Artwork\Modules\Worker\Services;

use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Support\Collection;

class WorkerService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly FreelancerService $freelancerService,
        private readonly ServiceProviderService $serviceProviderService,
    ) {
    }

    public function searchWorkers(string $search): Collection
    {
        $users = $this->userService->searchUsers($search);
        $freelancers = $this->freelancerService->searchFreelancers($search);
        $serviceProviders = $this->serviceProviderService->searchServiceProviders($search);

        return $users->merge($freelancers)->merge($serviceProviders);
    }
}
