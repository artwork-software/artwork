<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Repositories\UserContractRepository;

class UserContractService
{
    public function __construct(
        protected UserContractRepository $userContractRepository
    ) {
    }

    public function getAll()
    {
        return $this->userContractRepository->getAll();
    }


    public function create(array $data)
    {
        return $this->userContractRepository->create($data);
    }

    public function update(UserContract $userContract, array $data)
    {
        return $this->userContractRepository->update($userContract, $data);
    }

    public function delete(UserContract $userContract)
    {
        return $this->userContractRepository->delete($userContract);
    }
}
