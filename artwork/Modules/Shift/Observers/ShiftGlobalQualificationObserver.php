<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftGlobalQualificationObserver
{
    protected ShiftChangeRecorder $recorder;

    public function __construct(ShiftChangeRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    public function created(GlobalQualification $model): void
    {
        $this->recorder->record($model, 'created');
    }

    public function updated(GlobalQualification $model): void
    {
        $this->recorder->record($model, 'updated');
    }

    public function deleted(GlobalQualification $model): void
    {
        $this->recorder->record($model, 'deleted');
    }

    public function restored(GlobalQualification $model): void
    {
        // optional
        $this->recorder->record($model, 'updated');
    }

    public function forceDeleted(GlobalQualification $model): void
    {
        $this->recorder->record($model, 'deleted');
    }
}
