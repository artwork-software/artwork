<?php

namespace App\Console\Commands;

use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Console\Command;

class GetSage100Data extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-sage100-data {count}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get data from Sage100 and import it to budget.';

    public function __construct(
        private readonly Sage100Service $sage100Service
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->sage100Service->importDataToBudget($this->argument('count'));
        return 0;
    }
}
