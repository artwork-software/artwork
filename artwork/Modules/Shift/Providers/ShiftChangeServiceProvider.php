<?php

namespace Artwork\Modules\Shift\Providers;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Observers\ShiftGlobalQualificationObserver;
use Artwork\Modules\Shift\Observers\ShiftObserver;
use Artwork\Modules\Shift\Observers\ShiftsQualificationsObserver;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Illuminate\Support\ServiceProvider;

class ShiftChangeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShiftChangeRecorder::class, function () {
            return new ShiftChangeRecorder();
        });
    }

    public function boot(): void
    {
        Shift::observe(ShiftObserver::class);
        ShiftsQualifications::observe(ShiftsQualificationsObserver::class);
        GlobalQualification::observe(ShiftGlobalQualificationObserver::class);
    }
}
