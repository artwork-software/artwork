<?php

namespace Artwork\Modules\Accommodation\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Repositories\AccommodationRepository;

class AccommodationService
{
    public function __construct(
        protected AccommodationRepository $repository
    ) {
    }

    /**
     * Legt die Unterkunft mit ihrem CRM-Kontakt an, wie bei Freelancer*innen und Dienstleistern.
     */
    public function store(array $data)
    {
        $accommodation = $this->repository->create($data);
        $accommodation->createCrmContact();
        $accommodation->syncToCrm();

        return $accommodation;
    }

    public function update(Accommodation $accommodation, array $data)
    {
        $accommodation = $this->repository->update($accommodation, $data);
        $accommodation->syncToCrm();

        return $accommodation;
    }

    public function destroy(Accommodation $accommodation): void
    {
        $this->repository->destroy($accommodation);
    }
}
