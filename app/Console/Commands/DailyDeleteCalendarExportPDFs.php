<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DailyDeleteCalendarExportPDFs extends Command
{
    protected $signature = 'app:daily-delete-calendar-export-pdfs';

    protected $description = 'Delete all calendar export PDFs older than 24 hours';

    public function handle(): int
    {
        $path = storage_path('app/pdf/');
        $files = glob($path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            unlink($file);
        }

        return CommandAlias::SUCCESS;
    }
}
