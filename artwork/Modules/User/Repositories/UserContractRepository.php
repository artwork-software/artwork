<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Modules\User\Models\UserContract;

class UserContractRepository
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return UserContract::all();
    }

    public function create(array $data): UserContract
    {
        return UserContract::create($data);
    }

    public function update(UserContract $userContract, array $data): UserContract
    {
        $userContract->update($data);
        return $userContract;
    }

    public function delete(UserContract $userContract): bool
    {
        return $userContract->delete();
    }
}
