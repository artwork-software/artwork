<?php

namespace Artwork\Modules\Shift\Console\Commands;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

/**
 * Trägt den Urheber für Zuweisungen nach, die vor Einführung von
 * shift_workers.assigned_by_user_id entstanden sind.
 *
 * Quelle ist der Schichtverlauf: Die Zuweisung wurde dort schon immer mit
 * Verursacher protokolliert ("… wurde der Schicht als Mitarbeiter:in …
 * zugewiesen", causer = zuweisende Person). Ohne diesen Nachtrag fällt der
 * Konflikthinweis in Verfügbarkeiten/Abwesenheiten auf den Festschreibenden
 * zurück und nennt damit jemanden, der gar nicht eingeteilt hat.
 *
 * Idempotent: es werden ausschließlich leere assigned_by_user_id gefüllt.
 */
class BackfillShiftWorkerAssignedByCommand extends Command
{
    protected $signature = 'artwork:shift-workers:backfill-assigned-by
        {--dry-run : Nur anzeigen, was gesetzt würde – ohne Änderungen}
        {--once : Nur einmalig pro Umgebung ausführen (Marker in one_time_tasks). Für artwork:update.}';

    protected $description = 'Trägt fehlende Zuweisende (shift_workers.assigned_by_user_id) '
        . 'aus dem Schichtverlauf nach.';

    private const ONCE_KEY = 'artwork:shift-workers:backfill-assigned-by';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('once') && !$dryRun) {
            $this->ensureOnceTableExists();

            if (DB::table('one_time_tasks')->where('key', self::ONCE_KEY)->exists()) {
                $this->info('Nachtrag wurde bereits ausgeführt (--once) – übersprungen.');

                return self::SUCCESS;
            }
        }

        $pivotsByShift = $this->pivotsWithoutAssigner();

        if ($pivotsByShift === []) {
            $this->info('Keine Zuweisungen ohne Urheber gefunden.');
            $this->markDone($dryRun);

            return self::SUCCESS;
        }

        $names = $this->resolveWorkerNames($pivotsByShift);
        $updated = 0;

        // Aufsteigend: bei mehrfacher Zuweisung derselben Person gewinnt der jüngste Eintrag.
        Activity::query()
            ->where('log_name', 'shift')
            ->where('event', 'assigned')
            ->whereNotNull('causer_id')
            ->orderBy('id')
            ->chunkById(500, function ($activities) use (&$updated, $pivotsByShift, $names, $dryRun): void {
                foreach ($activities as $activity) {
                    $updated += $this->applyActivity($activity, $pivotsByShift, $names, $dryRun);
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Zuweisende nachgetragen: {$updated}");

        if (!$dryRun) {
            $this->refreshConflictScheduler();
        }

        $this->reportRemaining();

        $this->markDone($dryRun);

        return self::SUCCESS;
    }

    /**
     * Restmenge ausweisen: Zuweisungen, für die der Verlauf keinen Urheber hergibt
     * (Zuweisungen von vor dem Verlaufs-Logging, Importe/Seeder ohne Auth,
     * zwischenzeitlich umbenannte Personen). Nur für diese Fälle greift im
     * Konflikthinweis noch die Ersatzformulierung mit dem Festschreibenden.
     */
    private function reportRemaining(): void
    {
        $remaining = ShiftWorker::query()->whereNull('assigned_by_user_id')->count();

        if ($remaining === 0) {
            $this->info('Alle Zuweisungen haben jetzt einen Urheber.');

            return;
        }

        $onCommittedShifts = ShiftWorker::query()
            ->whereNull('assigned_by_user_id')
            ->whereHas('shift', fn ($query) => $query->where('is_committed', true))
            ->count();

        $this->warn(
            "Ohne Urheber bleiben: {$remaining} Zuweisungen ({$onCommittedShifts} auf festgeschriebenen "
            . 'Schichten) — dort nennt der Konflikthinweis den Festschreibenden als solchen.'
        );
    }

    /**
     * Einen Verlaufseintrag auf die passenden Pivot-Zeilen anwenden.
     *
     * @param array<int, array<int, string>> $pivotsByShift
     * @param array<string, string> $names
     * @return int Anzahl nachgetragener Zuweisungen
     */
    private function applyActivity(Activity $activity, array $pivotsByShift, array $names, bool $dryRun): int
    {
        $shiftId = (int) ($activity->properties['shift_id'] ?? $activity->subject_id);
        $workerName = $activity->properties['translation_key_placeholder_values'][0] ?? null;

        if ($shiftId === 0 || !is_string($workerName) || !isset($pivotsByShift[$shiftId])) {
            return 0;
        }

        $updated = 0;

        foreach ($pivotsByShift[$shiftId] as $pivotId => $employableKey) {
            if (($names[$employableKey] ?? null) !== $workerName) {
                continue;
            }

            if (!$dryRun) {
                ShiftWorker::withTrashed()
                    ->whereKey($pivotId)
                    ->whereNull('assigned_by_user_id')
                    ->update(['assigned_by_user_id' => $activity->causer_id]);
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * Bereits gespeicherte Konflikthinweise auf den nachgetragenen Urheber ziehen.
     * Ohne diesen Schritt blieben Bestandskonflikte beim Festschreibenden stehen,
     * bis die Verfügbarkeit zufällig neu geprüft wird.
     */
    private function refreshConflictScheduler(): void
    {
        $tables = [
            'vacation_conflicts' => ['vacations', 'vacation_id', 'vacationer_type', 'vacationer_id'],
            'availabilities_conflicts' => ['availabilities', 'availability_id', 'available_type', 'available_id'],
        ];

        foreach ($tables as $table => [$ownerTable, $foreignKey, $typeColumn, $idColumn]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'scheduler_source')) {
                continue;
            }

            $affected = DB::table($table)
                ->join($ownerTable, $ownerTable . '.id', '=', $table . '.' . $foreignKey)
                ->join('shift_workers', function ($join) use ($table, $ownerTable, $typeColumn, $idColumn): void {
                    $join->on('shift_workers.shift_id', '=', $table . '.shift_id')
                        ->on('shift_workers.employable_type', '=', $ownerTable . '.' . $typeColumn)
                        ->on('shift_workers.employable_id', '=', $ownerTable . '.' . $idColumn)
                        ->whereNull('shift_workers.deleted_at');
                })
                ->leftJoin('users as assigner', 'assigner.id', '=', 'shift_workers.assigned_by_user_id')
                ->whereNotNull('shift_workers.assigned_by_user_id')
                ->update([
                    $table . '.user_name' => DB::raw("CONCAT(assigner.first_name, ' ', assigner.last_name)"),
                    $table . '.scheduler_source' => 'assigned',
                    $table . '.scheduled_at' => DB::raw('shift_workers.created_at'),
                ]);

            $this->info("Konflikthinweise aktualisiert ({$table}): {$affected}");
        }
    }

    /**
     * Pivot-Zeilen ohne Urheber, gruppiert nach Schicht: [shift_id => [pivot_id => "Typ#Id"]]
     *
     * @return array<int, array<int, string>>
     */
    private function pivotsWithoutAssigner(): array
    {
        $grouped = [];

        ShiftWorker::withTrashed()
            ->whereNull('assigned_by_user_id')
            ->select(['id', 'shift_id', 'employable_type', 'employable_id'])
            ->orderBy('id')
            ->chunk(2000, function ($pivots) use (&$grouped): void {
                foreach ($pivots as $pivot) {
                    $grouped[(int) $pivot->shift_id][(int) $pivot->id] =
                        $pivot->employable_type . '#' . $pivot->employable_id;
                }
            });

        return $grouped;
    }

    /**
     * Anzeigenamen aller betroffenen Personen — exakt so, wie sie beim Logging
     * der Zuweisung in den Verlauf geschrieben wurden.
     *
     * @param array<int, array<int, string>> $pivotsByShift
     * @return array<string, string>
     */
    private function resolveWorkerNames(array $pivotsByShift): array
    {
        $idsByType = [];
        foreach ($pivotsByShift as $pivots) {
            foreach ($pivots as $employableKey) {
                [$type, $id] = explode('#', $employableKey);
                $idsByType[$type][(int) $id] = true;
            }
        }

        $names = [];
        foreach ($idsByType as $type => $ids) {
            if (!in_array($type, [User::class, Freelancer::class, ServiceProvider::class], true)) {
                continue;
            }

            $type::query()
                ->whereIn('id', array_keys($ids))
                ->get()
                ->each(function ($model) use (&$names, $type): void {
                    $names[$type . '#' . $model->id] = $type === User::class
                        ? $model->full_name
                        : $model->name;
                });
        }

        return $names;
    }

    private function markDone(bool $dryRun): void
    {
        if (!$this->option('once') || $dryRun) {
            return;
        }

        $this->ensureOnceTableExists();

        DB::table('one_time_tasks')->updateOrInsert(
            ['key' => self::ONCE_KEY],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Die Marker-Tabelle wird selbst angelegt, damit der Lauf nicht von der
     * Migrationsreihenfolge abhängt (artwork:update läuft ggf. vor `migrate`).
     */
    private function ensureOnceTableExists(): void
    {
        if (Schema::hasTable('one_time_tasks')) {
            return;
        }

        try {
            Schema::create('one_time_tasks', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $e) {
            // Möglicherweise hat ein paralleler Prozess die Tabelle bereits angelegt.
            if (!Schema::hasTable('one_time_tasks')) {
                throw $e;
            }
        }
    }
}
