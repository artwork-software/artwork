<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Regelpruefung ohne Speichern (ai-shift-planning, Core 5).
 *
 * Die Rule-Checks (`AbstractRuleCheck::createViolation()`) schreiben Verstoesse in die Datenbank
 * und loesen ueber `TriggersWorkflows` Workflows aus. Damit sie hypothetische Zuweisungen sehen,
 * ohne dass etwas davon bleibt, laeuft alles in `simulate()`:
 *
 * - `Model::withoutEvents()` nimmt **allen** Modellen den Event-Dispatcher (statisches Feld der
 *   Basisklasse): keine Workflow-Trigger, kein Activity-Log, keine Observer. Das deckt
 *   `ShiftRuleViolation::withoutEvents()` aus der Spec mit ab.
 * - eine Transaktion, die immer zurueckgerollt wird. `SafeBroadcast` sendet erst nach dem Commit
 *   (`DB::afterCommit`), zurueckgerollte Callbacks verfallen.
 * - hypothetische Schichten und Zuweisungen gehen per Query-Builder in die Tabellen — ohne
 *   Services, also ohne Benachrichtigung, Verlauf oder Broadcast. `ShiftRuleCheckContext` und
 *   `ShiftAssignmentPreflightService` lesen sie dann wie echte Zeilen.
 */
class AiShiftHypothesis
{
    public function __construct(private readonly ShiftRuleService $shiftRuleService)
    {
    }

    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function simulate(callable $work): mixed
    {
        return Model::withoutEvents(static function () use ($work): mixed {
            DB::beginTransaction();

            try {
                return $work();
            } finally {
                DB::rollBack();
            }
        });
    }

    /**
     * Hypothetische Schicht an einem Termin, mit einem Platz in der Funktion.
     */
    public function insertShift(
        int $eventId,
        int $craftId,
        int $qualificationId,
        Carbon $start,
        Carbon $end,
        int $breakMinutes,
        ?int $roomId,
        ?int $projectId
    ): Shift {
        $now = now();
        $shiftId = (int) DB::table('shifts')->insertGetId([
            'event_id' => $eventId,
            'craft_id' => $craftId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start' => $start->format('H:i:s'),
            'end' => $end->format('H:i:s'),
            'break_minutes' => $breakMinutes,
            'is_committed' => false,
            'in_workflow' => false,
            'shift_uuid' => (string) Str::uuid(),
            'event_start_day' => $start->toDateString(),
            'event_end_day' => $end->toDateString(),
            'room_id' => $roomId,
            'project_id' => $projectId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('shifts_qualifications')->insert([
            'shift_id' => $shiftId,
            'shift_qualification_id' => $qualificationId,
            'value' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Shift::query()->with(['craft', 'event', 'room'])->findOrFail($shiftId);
    }

    /**
     * Hypothetische Zuweisung; Pivot-Zeiten wie `ShiftWorkerRepository::createForShift()` (Kopie
     * der Schichtzeiten).
     */
    public function insertAssignment(
        User|Freelancer|ServiceProvider $person,
        Shift $shift,
        int $qualificationId
    ): int {
        $now = now();

        return (int) DB::table('shift_workers')->insertGetId([
            'shift_id' => $shift->id,
            'employable_type' => $person->getMorphClass(),
            'employable_id' => $person->getKey(),
            'shift_qualification_id' => $qualificationId,
            'is_overbooked' => false,
            'shift_count' => 1,
            'craft_abbreviation' => $shift->craft?->abbreviation,
            'start_date' => $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null,
            'end_date' => $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : null,
            'start_time' => $shift->start,
            'end_time' => $shift->end,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function removeAssignment(int $shiftWorkerId): void
    {
        DB::table('shift_workers')->where('id', $shiftWorkerId)->delete();
    }

    /**
     * Regellauf wie nach einer Zuweisung (`ShiftUpdateService::revalidateShiftRules()`), Ergebnis als
     * Schluessel `regel|datum` => Verstoss. Ausserhalb von `simulate()` aufgerufen, schreibt der Lauf
     * wie die Oberflaeche.
     *
     * @return array<string, ShiftRuleViolation>
     */
    public function violations(User $user, Carbon $from, Carbon $to): array
    {
        $keyed = [];

        foreach ($this->shiftRuleService->validateRulesForUser($user, $from->copy(), $to->copy()) as $violation) {
            if (!$violation instanceof ShiftRuleViolation || $violation->status !== 'active') {
                continue;
            }

            $keyed[$violation->shift_rule_id . '|' . $violation->violation_date?->toDateString()] = $violation;
        }

        return $keyed;
    }
}
