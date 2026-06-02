<?php

namespace Artwork\Modules\Shift\Console\Commands;

use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Services\ShiftPlanRequestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Einmaliger Reparatur-Command für Schichtplananfragen, die unter dem alten Auswahl-Bug
 * erstellt wurden und denen dadurch Schichten fehlen.
 *
 * Hängt je betroffener Anfrage alle aktuell "freien" Schichten des Gewerks/der KW nach derselben
 * Logik wie "Alle Schichten festsetzen" an (inkl. Verlauf-Einträge). Voraussetzung: die
 * Datenreparatur-Migration lief bereits, sodass zuvor hängengebliebene Schichten wieder frei sind.
 *
 * Standardmäßig werden nur PENDING-Anfragen verarbeitet (abgeschlossene Anfragen bleiben unberührt).
 */
class BackfillShiftPlanRequestShiftsCommand extends Command
{
    protected $signature = 'shift-plan-requests:backfill
        {--status=pending : Komma-separierte Status, die nachbefüllt werden (z.B. pending)}
        {--request= : Nur eine bestimmte Anfrage-ID nachbefüllen}
        {--dry-run : Nur anzeigen, was angehängt würde – ohne Änderungen}
        {--no-broadcast : Keine Websocket-Events senden}
        {--once : Nur einmalig pro Umgebung ausführen (Marker in one_time_tasks). Für artwork:update.}';

    protected $description = 'Hängt fehlende freie Schichten an bestehende Schichtplananfragen an (Reparatur des alten Auswahl-Bugs).';

    /**
     * Marker-Key in der one_time_tasks-Tabelle für den --once-Lauf.
     */
    private const ONCE_KEY = 'shift-plan-requests:backfill';

    public function __construct(
        private readonly ShiftPlanRequestService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $broadcast = ! $this->option('no-broadcast');
        $once = (bool) $this->option('once');

        // Einmalig-Modus: wenn bereits gelaufen, überspringen. Schützt davor, dass der
        // Reparatur-Lauf bei jedem Deployment (artwork:update) erneut Schichten anhängt.
        if ($once && ! $dryRun) {
            if (! Schema::hasTable('one_time_tasks')) {
                $this->warn('Tabelle one_time_tasks fehlt – bitte zuerst migrieren. Überspringe Backfill.');

                return self::SUCCESS;
            }

            if (DB::table('one_time_tasks')->where('key', self::ONCE_KEY)->exists()) {
                $this->info('Backfill wurde bereits ausgeführt (--once) – übersprungen.');

                return self::SUCCESS;
            }
        }

        $statuses = collect(explode(',', (string) $this->option('status')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();

        $query = ShiftPlanRequest::query()
            ->with('craft:id,name')
            ->orderBy('id');

        if ($requestId = $this->option('request')) {
            $query->where('id', (int) $requestId);
        } elseif (! empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        $requests = $query->get();

        if ($requests->isEmpty()) {
            $this->info('Keine passenden Schichtplananfragen gefunden.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d Anfrage(n)%s ...',
            $dryRun ? '[DRY-RUN] Prüfe' : 'Verarbeite',
            $requests->count(),
            $dryRun ? '' : ($broadcast ? ' (mit Broadcast)' : ' (ohne Broadcast)')
        ));

        $rows = [];
        $totalAttached = 0;

        foreach ($requests as $request) {
            $craftName = $request->craft?->name ?? ('#' . $request->craft_id);

            if ($dryRun) {
                $would = $this->service->freeShiftsForRequestQuery($request)->count();
                $totalAttached += $would;
                $rows[] = [$request->id, $craftName, $request->week_number, $request->year, $request->status, $would];
                continue;
            }

            $attachedIds = DB::transaction(
                fn () => $this->service->attachFreeShiftsToRequest($request, null, $broadcast)
            );

            $count = count($attachedIds);
            $totalAttached += $count;
            $rows[] = [$request->id, $craftName, $request->week_number, $request->year, $request->status, $count];
        }

        $this->table(
            ['Request', 'Gewerk', 'KW', 'Jahr', 'Status', $dryRun ? 'Würde anhängen' : 'Angehängt'],
            $rows
        );

        $this->info(sprintf(
            '%s%d Schicht(en) %s.',
            $dryRun ? '[DRY-RUN] ' : '',
            $totalAttached,
            $dryRun ? 'würden angehängt' : 'angehängt'
        ));

        // Einmalig-Marker setzen, damit der Lauf bei künftigen Deployments übersprungen wird.
        if ($once && ! $dryRun && Schema::hasTable('one_time_tasks')) {
            DB::table('one_time_tasks')->updateOrInsert(
                ['key' => self::ONCE_KEY],
                ['executed_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return self::SUCCESS;
    }
}
