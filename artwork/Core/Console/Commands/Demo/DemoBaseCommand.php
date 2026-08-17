<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Database\Seeders\Demo\DemoBrandingSeeder;
use Database\Seeders\Demo\DemoContractSeeder;
use Database\Seeders\Demo\DemoPrintLayoutSeeder;
use Database\Seeders\Demo\DemoShiftBaseSeeder;
use Database\Seeders\Demo\DemoStructureSeeder;
use Database\Seeders\Demo\DemoTabCompositionSeeder;

class DemoBaseCommand extends BaseDemoCommand
{
    protected $signature = 'artwork:demo:base';

    protected $description = 'Demo-Grundlagen "Artwork Testhaus": Branding, Gewerke/Funktionen, Verträge/Regeln, '
        . 'Räume/Termintypen, Tab-Ergänzungen und Drucklayouts (additiv & idempotent).';

    private const SEEDERS = [
        DemoBrandingSeeder::class,
        DemoShiftBaseSeeder::class,
        DemoContractSeeder::class,
        DemoStructureSeeder::class,
        DemoTabCompositionSeeder::class,
        DemoPrintLayoutSeeder::class,
    ];

    public function handle(): int
    {
        $this->actAsSeedUser();

        $this->info('=== artwork:demo:base – Grundlagen des Artwork Testhauses ===');

        foreach (self::SEEDERS as $seederClass) {
            $this->newLine();
            $this->line('<comment>' . class_basename($seederClass) . '</comment>');
            $seeder = new $seederClass();
            $seeder->setContainer($this->getLaravel())->setCommand($this)->__invoke();
        }

        $this->newLine();
        $this->info('Fertig. Nächster Schritt: artwork:demo:workers');

        return self::SUCCESS;
    }
}
