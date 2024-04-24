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
}
