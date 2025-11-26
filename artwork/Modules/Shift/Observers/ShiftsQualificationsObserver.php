<?php

namespace Artwork\Modules\Shift\Observers;

use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;

class ShiftsQualificationsObserver
{
    public function created(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'created');
    }

    public function updated(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'updated');
    }

    public function deleted(ShiftsQualifications $model): void
    {
        app(ShiftChangeRecorder::class)->record($model, 'deleted');
    }
}
