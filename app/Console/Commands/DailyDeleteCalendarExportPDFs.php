<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DailyDeleteCalendarExportPDFs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-delete-calendar-export-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all calendar export PDFs older than 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = storage_path('app/pdf/');
        $files = glob($path . '*'); // get all file names
        foreach($files as $file){ // iterate files
            unlink($file);
        }

        return CommandAlias::SUCCESS;
    }
}
