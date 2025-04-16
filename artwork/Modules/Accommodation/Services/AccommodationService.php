<?php

namespace Artwork\Modules\Accommodation\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Repositories\AccommodationRepository;

class AccommodationService
{
    public function __construct(
        protected AccommodationRepository $repository
    ) {}

    public function store(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(Accommodation $accommodation, array $data)
    {
        return $this->repository->update($accommodation, $data);
    }

    public function destroy(Accommodation $accommodation){
        $this->repository->destroy($accommodation);
    }
}