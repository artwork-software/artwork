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

    public function setUsersForShifts(?array $usersForShifts): void
    {
        $this->usersForShifts = $usersForShifts;
    }

    public function setFreelancersForShifts(?array $freelancersForShifts): void
    {
        $this->freelancersForShifts = $freelancersForShifts;
    }

    public function setServiceProvidersForShifts(?array $serviceProvidersForShifts): void
    {
        $this->serviceProvidersForShifts = $serviceProvidersForShifts;
    }

    public function setEventsWithRelevant(?array $eventsWithRelevant): void
    {
        $this->eventsWithRelevant = $eventsWithRelevant;
    }

    public function setCrafts(?Collection $crafts): void
    {
        $this->crafts = $crafts;
    }

    public function setCurrentUserCrafts(?Collection $currentUserCrafts): void
    {
        $this->currentUserCrafts = $currentUserCrafts;
    }

    public function setShiftQualifications(?Collection $shiftQualifications): void
    {
        $this->shiftQualifications = $shiftQualifications;
    }
}
