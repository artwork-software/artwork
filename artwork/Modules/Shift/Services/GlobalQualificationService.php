<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Repositories\GlobalQualificationRepository;
use Artwork\Modules\User\Models\User;

class GlobalQualificationService
{
    public function __construct(
        protected GlobalQualificationRepository $globalQualificationRepository
    ) {
    }

    public function delete(GlobalQualification $globalQualification): void
    {
        $this->globalQualificationRepository->destroy($globalQualification);
    }

    public function deleteAll(): void
    {
        $globalQualifications = $this->globalQualificationRepository->getAll();
        foreach ($globalQualifications as $globalQualification) {
            $this->globalQualificationRepository->destroy($globalQualification);
        }
    }

    public function create(array $data): void
    {
        $this->globalQualificationRepository->create($data);
    }

    public function update(GlobalQualification $globalQualification, array $data): void
    {
        $this->globalQualificationRepository->update($globalQualification, $data);
    }

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->globalQualificationRepository->getAll();
    }

    /**
     * Aktiviert oder deaktiviert eine GlobalQualification für ein beliebiges qualifiable Model (User, Freelancer, ServiceProvider)
     */
    public function activateOrDeactivateInQualifiable(GlobalQualification $globalQualification, $qualifiable): void
    {
        if ($qualifiable->globalQualifications->contains($globalQualification->id)) {
            $qualifiable->globalQualifications()->detach($globalQualification->id);
        } else {
            $qualifiable->globalQualifications()->attach($globalQualification->id);
        }
    }
}
