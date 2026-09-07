<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * „Woche kopieren": legt die Schichten einer Quell-KW in einer oder mehreren Ziel-KWs neu an.
 *
 * Kopiert werden Raum, Gewerk, Zeiten (Wochentag bleibt, Über-Mitternacht bleibt), Pause,
 * Notiz, Schichtgruppe, Projekt, Funktionsplätze (value, overbooked_value = 0) und globale
 * Qualifikationen. NICHT kopiert werden Personen, Festschreibung, Workflow-Status,
 * Serien-Kennung (shift_uuid) und Termin-Bezug (event_id). Ist im Zielraum am Zieltag bereits
 * eine Schicht mit gleichem Gewerk und gleicher Start-/Endzeit vorhanden, wird die Quellschicht
 * übersprungen.
 */
class ShiftWeekCopyService
{
    public function __construct(
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
    ) {
    }

    /**
     * Montag 00:00 und Sonntag 23:59:59 der ISO-Kalenderwoche.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function weekBounds(int $week, int $year): array
    {
        $monday = Carbon::now()->setISODate($year, $week, 1)->startOfDay();

        return [$monday, $monday->copy()->addDays(6)->endOfDay()];
    }

    /**
     * Nicht gelöschte Schichten der Quell-KW (Mo–So nach start_date), optional nach Gewerken/Räumen gefiltert.
     *
     * @param array<int, int>|null $craftIds
     * @param array<int, int>|null $roomIds
     * @return Collection<int, Shift>
     */
    public function sourceShifts(int $week, int $year, ?array $craftIds = null, ?array $roomIds = null): Collection
    {
        [$monday, $sunday] = self::weekBounds($week, $year);

        return Shift::query()
            ->whereDate('start_date', '>=', $monday->toDateString())
            ->whereDate('start_date', '<=', $sunday->toDateString())
            ->when($craftIds !== null, fn ($query) => $query->whereIn('craft_id', $craftIds))
            ->when($roomIds !== null, fn ($query) => $query->whereIn('room_id', $roomIds))
            ->with(['shiftsQualifications', 'globalQualifications'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->orderBy('id')
            ->get();
    }

    /**
     * Kopiert die übergebenen Quellschichten in eine Ziel-KW (eine Transaktion je Zielwoche).
     *
     * @param Collection<int, Shift> $sourceShifts
     * @return array{
     *     week: int,
     *     year: int,
     *     created: int,
     *     skipped: int,
     *     shift_ids: array<int, int>,
     *     skipped_shifts: array<int, array{date: string, room: string|null, start: string|null, end: string|null, craft: string|null}>
     * }
     */
    public function copyToWeek(
        Collection $sourceShifts,
        int $sourceWeek,
        int $sourceYear,
        int $targetWeek,
        int $targetYear
    ): array {
        [$sourceMonday] = self::weekBounds($sourceWeek, $sourceYear);
        [$targetMonday, $targetSunday] = self::weekBounds($targetWeek, $targetYear);

        // Versatz in ganzen Tagen (über round, damit ein DST-Wechsel keinen Tag verschluckt)
        $dayOffset = (int) round(($targetMonday->getTimestamp() - $sourceMonday->getTimestamp()) / 86400);

        // Belegte Zielzeiten einmal laden statt je Quellschicht abzufragen
        $occupied = Shift::query()
            ->whereDate('start_date', '>=', $targetMonday->toDateString())
            ->whereDate('start_date', '<=', $targetSunday->toDateString())
            ->get(['id', 'room_id', 'craft_id', 'start_date', 'start', 'end'])
            ->mapWithKeys(fn (Shift $shift) => [
                self::occupancyKey(
                    $shift->room_id,
                    $shift->craft_id,
                    Carbon::parse($shift->start_date)->toDateString(),
                    $shift->start,
                    $shift->end
                ) => true,
            ])
            ->all();

        $sourceShifts->loadMissing(['room:id,name', 'craft:id,name,abbreviation']);

        return DB::transaction(function () use (
            $sourceShifts,
            $dayOffset,
            $occupied,
            $targetWeek,
            $targetYear
        ): array {
            $createdIds = [];
            $skipped = [];

            foreach ($sourceShifts as $source) {
                $newStartDate = Carbon::parse($source->start_date)->addDays($dayOffset)->toDateString();
                $newEndDate = Carbon::parse($source->end_date ?? $source->start_date)
                    ->addDays($dayOffset)
                    ->toDateString();

                $key = self::occupancyKey(
                    $source->room_id,
                    $source->craft_id,
                    $newStartDate,
                    $source->start,
                    $source->end
                );

                if (isset($occupied[$key])) {
                    $skipped[] = [
                        'date' => Carbon::parse($newStartDate)->format('d.m.Y'),
                        'room' => $source->room?->name,
                        'start' => $source->start,
                        'end' => $source->end,
                        'craft' => $source->craft?->abbreviation ?? $source->craft?->name,
                    ];
                    continue;
                }

                $shift = new Shift([
                    'event_id' => null,
                    'room_id' => $source->room_id,
                    'craft_id' => $source->craft_id,
                    'project_id' => $source->project_id,
                    'shift_group_id' => $source->shift_group_id,
                    'start_date' => $newStartDate,
                    'end_date' => $newEndDate,
                    'start' => $source->start,
                    'end' => $source->end,
                    'break_minutes' => $source->break_minutes,
                    'description' => $source->description,
                    'is_committed' => false,
                    'in_workflow' => false,
                    'current_request_id' => null,
                    'shift_uuid' => null,
                    'committing_user_id' => null,
                    'workflow_rejection_reason' => null,
                ]);
                // Activity-Log „created" schreibt Spatie über LogsActivity mit dem eingeloggten Causer.
                $shift->save();

                foreach ($source->shiftsQualifications as $sourceQualification) {
                    $this->shiftsQualificationsService->createShiftsQualificationForShift(
                        $shift->id,
                        [
                            'shift_qualification_id' => $sourceQualification->shift_qualification_id,
                            'value' => (int) $sourceQualification->value,
                            'overbooked_value' => 0,
                        ]
                    );
                }

                foreach ($source->globalQualifications as $globalQualification) {
                    $shift->globalQualifications()->attach($globalQualification->id, [
                        'quantity' => (int) ($globalQualification->pivot->quantity ?? 0),
                    ]);
                }

                // Zwei identische Quellschichten dürfen im Ziel nicht beide entstehen
                $occupied[$key] = true;
                $createdIds[] = $shift->id;
            }

            return [
                'week' => $targetWeek,
                'year' => $targetYear,
                'created' => count($createdIds),
                'skipped' => count($skipped),
                'shift_ids' => $createdIds,
                'skipped_shifts' => $skipped,
            ];
        });
    }

    private static function occupancyKey(
        int|string|null $roomId,
        int|string|null $craftId,
        string $date,
        ?string $start,
        ?string $end
    ): string {
        $normalize = static fn (?string $time): string => $time ? Carbon::parse($time)->format('H:i') : '';

        return implode('|', [
            (string) ($roomId ?? ''),
            (string) ($craftId ?? ''),
            $date,
            $normalize($start),
            $normalize($end),
        ]);
    }
}
