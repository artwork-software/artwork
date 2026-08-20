<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Database\Seeders\Demo\DemoWorkerLinkSeeder;
use Database\Seeders\Demo\DemoWorkerSeeder;

class DemoWorkersCommand extends BaseDemoCommand
{
    protected $signature = 'artwork:demo:workers';

    protected $description = 'Demo-Belegschaft anlegen und ALLE Worker (auch Bestand) mit Gewerken, Funktionen, '
        . 'Verträgen und Arbeitszeitmustern verknüpfen (additiv & idempotent).';

    public function handle(): int
    {
        $this->actAsSeedUser();

        $this->info('=== artwork:demo:workers – Belegschaft & Verknüpfungs-Workflow ===');

        foreach ([DemoWorkerSeeder::class, DemoWorkerLinkSeeder::class] as $seederClass) {
            $this->newLine();
            $this->line('<comment>' . class_basename($seederClass) . '</comment>');
            $seeder = new $seederClass();
            $seeder->setContainer($this->getLaravel())->setCommand($this)->__invoke();
        }

        $this->newLine();
        $this->info('Fertig. Nächster Schritt: artwork:demo:projects --from=YYYY-MM --months=N');

        return self::SUCCESS;
    }
}
