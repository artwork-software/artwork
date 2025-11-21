<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftObserver
{
    public function __construct(
        protected ShiftChangeRecorder $recorder
    ) {}

    public function created(Shift $shift): void
    {
        $this->recorder->record($shift, 'created');
    }

    public function updated(Shift $shift): void
    {
        $this->recorder->record($shift, 'updated');
    }

    public function deleted(Shift $shift): void
    {
        $this->recorder->record($shift, 'deleted');
    }
}
