<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Models\Permission;
use Database\Seeders\ProductionDatabaseSeeder;
use Illuminate\Console\Command;

class SeedProductionCommand extends Command
{
    protected $signature = 'db:seed:production {--force}';

    protected $description = 'Seeds the production database with necessary data';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (Permission::first() && !$this->option('force')) {
            $this->error('Database is already seeded. Use --force to seed again');
            return Command::FAILURE;
        }
        $this->call(ProductionDatabaseSeeder::class);

        return Command::SUCCESS;
    }
}
