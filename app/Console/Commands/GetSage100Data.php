<?php

namespace App\Console\Commands;

use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Console\Command;

class GetSage100Data extends Command
{
    protected $signature = 'app:get-sage100-data {count?}';

    protected $description = 'Get data from Sage100 and import it to budget.';

    public function __construct(
        private readonly Sage100Service $sage100Service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        return $this->sage100Service->importDataToBudget($this->argument('count'));
    }
}
