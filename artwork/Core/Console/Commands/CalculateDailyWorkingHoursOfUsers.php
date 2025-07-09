<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Illuminate\Console\Command;

class CalculateDailyWorkingHoursOfUsers extends Command
{

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'artwork:calculate-daily-working-hours-of-users';

    /**
     * The console command description.
     */
    protected $description = 'Calculate daily working hours of users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Calculating daily working hours of users...');

        $workTimeBookingService = app(WorkTimeBookingService::class);
        $workTimeBookingService->calculateDailyWorkingHours();
        $this->info('Daily working hours of users calculated successfully.');
    }
}
