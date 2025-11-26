<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftUserObserver
{
    public function __construct(
        protected ShiftChangeRecorder $recorder
    ) {}

    public function created(ShiftUser $pivot): void
    {
        $this->recorder->record($pivot, 'created');
    }

    public function updated(ShiftUser $pivot): void
    {
        $this->recorder->record($pivot, 'updated');
    }

    public function deleted(ShiftUser $pivot): void
    {
        $this->recorder->record($pivot, 'deleted');
    }
}
