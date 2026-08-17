<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Carbon\Carbon;
use Database\Seeders\Demo\DemoProjectDayAssignmentSeeder;
use Database\Seeders\Demo\DemoProjectSeeder;
use Database\Seeders\Demo\DemoShiftAssignmentSeeder;
use Database\Seeders\Demo\DemoShiftPlanRequestSeeder;
use Database\Seeders\Demo\DemoShiftSeeder;
use Database\Seeders\Demo\DemoTodayProgramSeeder;

class DemoProjectsCommand extends BaseDemoCommand
{
    protected $signature = 'artwork:demo:projects
        {--from= : Startmonat (YYYY-MM), Standard: vor 2 Monaten}
        {--months=6 : Anzahl Monate ab Startmonat}
        {--dry-run : Nur den Projektplan ausgeben, nichts schreiben}';

    protected $description = 'Demo-Projekte mit Tab-Inhalten, Budget, Terminen und besetzten Schichten für einen '
        . 'Monats-Zeitraum erzeugen (additiv & idempotent; auch zum späteren Nachfüllen einzelner Monate).';

    public function handle(): int
    {
        $this->actAsSeedUser();

        $from = $this->option('from');
        if ($from !== null && !preg_match('/^\d{4}-\d{2}$/', $from)) {
            $this->error('Ungültiges --from-Format, erwartet YYYY-MM (z.B. 2026-09).');

            return self::FAILURE;
        }
        $months = max(1, (int) $this->option('months'));
        $dryRun = (bool) $this->option('dry-run');

        $windowStart = ($from !== null ? Carbon::createFromFormat('Y-m', $from) : Carbon::now()->subMonths(2))
            ->startOfMonth();
        $this->info(sprintf(
            '=== artwork:demo:projects – %s bis %s%s ===',
            $windowStart->format('m/Y'),
            $windowStart->copy()->addMonths($months - 1)->format('m/Y'),
            $dryRun ? ' (dry-run)' : ''
        ));

        $projectSeeder = new DemoProjectSeeder();
        $projectSeeder->from = $from;
        $projectSeeder->months = $months;
        $projectSeeder->dryRun = $dryRun;
        $projectSeeder->setContainer($this->getLaravel())->setCommand($this)->__invoke();

        if ($dryRun) {
            $this->info('Dry-run beendet – nichts geschrieben.');

            return self::SUCCESS;
        }

        foreach (
            [
                DemoTodayProgramSeeder::class,
                DemoShiftSeeder::class,
                DemoShiftAssignmentSeeder::class,
                DemoProjectDayAssignmentSeeder::class,
                DemoShiftPlanRequestSeeder::class,
            ] as $seederClass
        ) {
            $this->newLine();
            $this->line('<comment>' . class_basename($seederClass) . '</comment>');
            $seeder = new $seederClass();
            $seeder->from = $from;
            $seeder->months = $months;
            $seeder->setContainer($this->getLaravel())->setCommand($this)->__invoke();
        }

        $this->newLine();
        $this->info('Fertig. Optional: artwork:demo:extras für denselben Zeitraum.');

        return self::SUCCESS;
    }
}
