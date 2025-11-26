<?php

namespace Artwork\Modules\Shift\Providers;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider as ShiftServiceProviderPivot;
use Artwork\Modules\Shift\Observers\ShiftGlobalQualificationObserver;
use Artwork\Modules\Shift\Observers\ShiftObserver;
use Artwork\Modules\Shift\Observers\ShiftsQualificationsObserver;
use Artwork\Modules\Shift\Observers\ShiftUserObserver;
use Artwork\Modules\Shift\Observers\ShiftFreelancerObserver;
use Artwork\Modules\Shift\Observers\ShiftServiceProviderObserver;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Illuminate\Support\ServiceProvider;

class ShiftChangeServiceProvider extends ServiceProvider
{
    /**
     * Register bindings.
     */
    public function register(): void
    {
        // Falls du spezielle Konfigurationen brauchst, kannst du sie hier machen.
        // Den Recorder kannst du als Singleton binden (optional, aber ordentlich):

        $this->app->singleton(ShiftChangeRecorder::class, function () {
            return new ShiftChangeRecorder();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer für Shift (Stammdaten)
        Shift::observe(ShiftObserver::class);
        ShiftsQualifications::observe(ShiftsQualificationsObserver::class);
        GlobalQualification::observe(ShiftGlobalQualificationObserver::class);

        /** aktuell nicht verwendet */
        // Observer für Pivot: shift_user
        //ShiftUser::observe(ShiftUserObserver::class);

        // Observer für Pivot: shifts_freelancers
        //ShiftFreelancer::observe(ShiftFreelancerObserver::class);

        // Observer für Pivot: shifts_service_providers
        //ShiftServiceProviderPivot::observe(ShiftServiceProviderObserver::class);
    }
}
