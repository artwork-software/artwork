<?php

namespace App\Console\Commands;

use Database\Seeders\ProductionDatabaseSeeder;
use Illuminate\Console\Command;

class SeedProductionCommand extends Command
{
    protected $signature = 'db:seed:production';

    protected $description = 'Seeds the production database with necessary data';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->call(ProductionDatabaseSeeder::class);

        return Command::SUCCESS;
    }
}
