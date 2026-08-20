<?php

declare(strict_types=1);

namespace Artwork\Core\Console\Commands\Demo;

use Carbon\Carbon;

class DemoAllCommand extends BaseDemoCommand
{
    protected $signature = 'artwork:demo:all
        {--from= : Startmonat (YYYY-MM), Standard: vor 2 Monaten}
        {--months=6 : Anzahl Monate ab Startmonat}';

    protected $description = 'Kompletter Demo-Seed des "Artwork Testhauses": base → workers → projects → extras '
        . '(additiv & idempotent).';

    public function handle(): int
    {
        $this->actAsSeedUser();

        $from = $this->option('from') ?? Carbon::now()->subMonths(2)->format('Y-m');
        $months = max(1, (int) $this->option('months'));

        $this->info(sprintf('=== artwork:demo:all – Zeitfenster %s, %d Monate ===', $from, $months));

        $steps = [
            ['artwork:demo:base', []],
            ['artwork:demo:workers', []],
            ['artwork:demo:projects', ['--from' => $from, '--months' => $months]],
            ['artwork:demo:extras', ['--from' => $from, '--months' => $months]],
        ];

        foreach ($steps as [$command, $arguments]) {
            $this->newLine();
            $exitCode = $this->call($command, $arguments);
            if ($exitCode !== self::SUCCESS) {
                $this->error(sprintf('%s fehlgeschlagen (Exit-Code %d) – Abbruch.', $command, $exitCode));

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Demo-Seed abgeschlossen. Empfohlen: Cache leeren (php artisan cache:clear).');

        return self::SUCCESS;
    }
}
