<?php

namespace Artwork\Modules\ProjectTab\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Illuminate\Database\Eloquent\Collection;

class ShiftsDto extends BaseDto
{
    public ?array $usersForShifts = null;

    public ?array $freelancersForShifts = null;

    public ?array $serviceProvidersForShifts = null;

    public ?array $eventsWithRelevant = null;

    public ?Collection $crafts = null;

    public ?Collection $currentUserCrafts = null;

    public ?Collection $shiftQualifications = null;

    public ?Collection $shiftTimePresets = null;

    public function setShiftTimePresets(?Collection $shiftTimePresets): self
    {
        $this->shiftTimePresets = $shiftTimePresets;

        return $this;
    }

    public function setUsersForShifts(?array $usersForShifts): self
    {
        $this->usersForShifts = $usersForShifts;

        return $this;
    }

    public function setFreelancersForShifts(?array $freelancersForShifts): self
    {
        $this->freelancersForShifts = $freelancersForShifts;

        return $this;
    }

    public function setServiceProvidersForShifts(?array $serviceProvidersForShifts): self
    {
        $this->serviceProvidersForShifts = $serviceProvidersForShifts;

        return $this;
    }

    public function setEventsWithRelevant(?array $eventsWithRelevant): self
    {
        $this->eventsWithRelevant = $eventsWithRelevant;

        return $this;
    }

    public function setCrafts(?Collection $crafts): self
    {
        $this->crafts = $crafts;

        return $this;
    }

    public function setCurrentUserCrafts(?Collection $currentUserCrafts): self
    {
        $this->currentUserCrafts = $currentUserCrafts;

        return $this;
    }

    public function setShiftQualifications(?Collection $shiftQualifications): self
    {
        $this->shiftQualifications = $shiftQualifications;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUsersForShifts(): ?array
    {
        return $this->usersForShifts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFreelancersForShifts(): ?array
    {
        return $this->freelancersForShifts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getServiceProvidersForShifts(): ?array
    {
        return $this->serviceProvidersForShifts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventsWithRelevant(): ?array
    {
        return $this->eventsWithRelevant;
    }

    public function getCrafts(): ?Collection
    {
        return $this->crafts;
    }

    public function getCurrentUserCrafts(): ?Collection
    {
        return $this->currentUserCrafts;
    }

    public function getShiftQualifications(): ?Collection
    {
        return $this->shiftQualifications;
    }
}
