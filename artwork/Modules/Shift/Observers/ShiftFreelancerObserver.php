<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftFreelancerObserver
{
    public function __construct(
        protected ShiftChangeRecorder $recorder
    ) {}

    public function created(ShiftFreelancer $pivot): void
    {
        $this->recorder->record($pivot, 'created');
    }

    public function updated(ShiftFreelancer $pivot): void
    {
        $this->recorder->record($pivot, 'updated');
    }

    public function deleted(ShiftFreelancer $pivot): void
    {
        $this->recorder->record($pivot, 'deleted');
    }
}
