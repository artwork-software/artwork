<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Database\Seeders\Demo\DemoArtistResidencySeeder;
use Database\Seeders\Demo\DemoAvailabilitySeeder;
use Database\Seeders\Demo\DemoBiSeeder;
use Database\Seeders\Demo\DemoCrmSeeder;
use Database\Seeders\Demo\DemoInventorySeeder;
use Database\Seeders\Demo\DemoMaterialIssueSeeder;

class DemoExtrasCommand extends BaseDemoCommand
{
    protected $signature = 'artwork:demo:extras
        {--from= : Startmonat (YYYY-MM), Standard: vor 2 Monaten}
        {--months=6 : Anzahl Monate ab Startmonat}';

    protected $description = 'Demo-Extras: Urlaube + Arbeitszeit-Historie, Inventar, Materialausgaben und '
        . 'Künstler*innenaufenthalte für den Zeitraum (additiv & idempotent).';

    public function handle(): int
    {
        $this->actAsSeedUser();

        $from = $this->option('from');
        if ($from !== null && !preg_match('/^\d{4}-\d{2}$/', $from)) {
            $this->error('Ungültiges --from-Format, erwartet YYYY-MM (z.B. 2026-09).');

            return self::FAILURE;
        }
        $months = max(1, (int) $this->option('months'));

        $this->info('=== artwork:demo:extras – Inventar, Ausgaben, Aufenthalte, Arbeitszeit ===');

        foreach (
            [
                DemoInventorySeeder::class,
                DemoMaterialIssueSeeder::class,
                DemoArtistResidencySeeder::class,
                DemoAvailabilitySeeder::class,
                DemoCrmSeeder::class,
                DemoBiSeeder::class,
            ] as $seederClass
        ) {
            $this->newLine();
            $this->line('<comment>' . class_basename($seederClass) . '</comment>');
            $seeder = new $seederClass();
            if (property_exists($seeder, 'from')) {
                $seeder->from = $from;
                $seeder->months = $months;
            }
            $seeder->setContainer($this->getLaravel())->setCommand($this)->__invoke();
        }

        $this->newLine();
        $this->info('Fertig.');

        return self::SUCCESS;
    }
}
