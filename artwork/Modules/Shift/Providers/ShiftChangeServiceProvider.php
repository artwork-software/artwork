<?php

namespace Artwork\Modules\Shift\Providers;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Observers\ShiftGlobalQualificationObserver;
use Artwork\Modules\Shift\Observers\ShiftObserver;
use Artwork\Modules\Shift\Observers\ShiftsQualificationsObserver;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Shift\Services\ShiftConfirmationEligibilityService;
use Illuminate\Support\ServiceProvider;

class ShiftChangeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShiftChangeRecorder::class, function () {
            return new ShiftChangeRecorder();
        });

        // scoped statt singleton: Octane/Swoole hält Singletons über Requests hinweg,
        // die memoisierten berechtigten User-IDs müssen aber je Request frisch sein.
        $this->app->scoped(ShiftConfirmationEligibilityService::class);
    }

    public function boot(): void
    {
        Shift::observe(ShiftObserver::class);
        ShiftsQualifications::observe(ShiftsQualificationsObserver::class);
        GlobalQualification::observe(ShiftGlobalQualificationObserver::class);
    }
}
