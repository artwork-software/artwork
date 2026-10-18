<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Zählstände der Zuweisungen für die Kalender-Kacheln (CalendarShiftDTO), per Aggregat-Abfrage statt
 * über die Worker-Relationen: die Kachel zeigt nur besetzt/gefordert, das Hydrieren aller zugewiesenen
 * Personen samt Pivot und globalen Qualifikationen kostete je Kalendermonat die Hälfte der Serverzeit.
 *
 * Gezählt wird wie über Shift::users()/freelancer()/serviceProvider(): nicht gelöschte Zeilen in
 * shift_workers, deren Person existiert; globale Qualifikationen wie HasShifts::globalQualifications().
 */
class CalendarShiftWorkerCounts
{
    private const CHUNK_SIZE = 2000;

    /**
     * @param array<int, int> $shiftIds
     * @return array<int, array{0: array<int, int>, 1: array<int, int>, 2: int}>
     *         [shiftId] => [Anzahl je shift_qualification_id, Anzahl je globaler Qualifikation, Gesamtzahl]
     */
    public function forShiftIds(array $shiftIds): array
    {
        $counts = [];

        foreach (array_chunk(array_values(array_unique($shiftIds)), self::CHUNK_SIZE) as $chunk) {
            $qualificationRows = $this->assignedWorkers($chunk)
                ->select([
                    'sw.shift_id',
                    'sw.shift_qualification_id',
                    DB::raw('COUNT(*) as worker_count'),
                ])
                ->groupBy('sw.shift_id', 'sw.shift_qualification_id')
                ->get();

            foreach ($qualificationRows as $row) {
                $shiftId = (int) $row->shift_id;
                $counts[$shiftId] ??= [[], [], 0];
                $counts[$shiftId][2] += (int) $row->worker_count;

                if ($row->shift_qualification_id !== null) {
                    $qualificationId = (int) $row->shift_qualification_id;
                    $counts[$shiftId][0][$qualificationId] =
                        ($counts[$shiftId][0][$qualificationId] ?? 0) + (int) $row->worker_count;
                }
            }

            $globalQualificationRows = $this->assignedWorkers($chunk)
                ->join('global_qualifiables as gq', function ($join): void {
                    $join->on('gq.qualifiable_id', '=', 'sw.employable_id')
                        ->on('gq.qualifiable_type', '=', 'sw.employable_type');
                })
                ->join('global_qualifications as g', 'g.id', '=', 'gq.global_qualification_id')
                ->select([
                    'sw.shift_id',
                    'gq.global_qualification_id',
                    DB::raw('COUNT(*) as worker_count'),
                ])
                ->groupBy('sw.shift_id', 'gq.global_qualification_id')
                ->get();

            foreach ($globalQualificationRows as $row) {
                $shiftId = (int) $row->shift_id;
                $counts[$shiftId] ??= [[], [], 0];
                $counts[$shiftId][1][(int) $row->global_qualification_id] = (int) $row->worker_count;
            }
        }

        return $counts;
    }

    /**
     * @param array<int, int> $shiftIds
     */
    private function assignedWorkers(array $shiftIds): Builder
    {
        return DB::table('shift_workers as sw')
            ->whereIn('sw.shift_id', $shiftIds)
            ->whereNull('sw.deleted_at')
            ->where(function (Builder $query): void {
                foreach ([User::class, Freelancer::class, ServiceProvider::class] as $workerClass) {
                    $table = (new $workerClass())->getTable();
                    $query->orWhere(function (Builder $typeQuery) use ($workerClass, $table): void {
                        $typeQuery->where('sw.employable_type', $workerClass)
                            ->whereExists(function (Builder $exists) use ($table): void {
                                $exists->select(DB::raw(1))
                                    ->from($table)
                                    ->whereColumn($table . '.id', 'sw.employable_id');
                            });
                    });
                }
            });
    }
}
