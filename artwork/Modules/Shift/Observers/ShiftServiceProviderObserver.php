<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftServiceProviderObserver
{
    public function __construct(
        protected ShiftChangeRecorder $recorder
    ) {}

    public function created(ShiftServiceProvider $pivot): void
    {
        $this->recorder->record($pivot, 'created');
    }

    public function updated(ShiftServiceProvider $pivot): void
    {
        $this->recorder->record($pivot, 'updated');
    }

    public function deleted(ShiftServiceProvider $pivot): void
    {
        $this->recorder->record($pivot, 'deleted');
    }
}
