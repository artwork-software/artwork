<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/people/{type}/{id}/workload` (ai-shift-planning).
 *
 * Wochen und Monate sind die **vollstaendigen** ISO-Wochen bzw. Kalendermonate, die der Zeitraum
 * beruehrt — eine Grenze „pro Woche" laesst sich nur gegen die ganze Woche pruefen.
 * `target_minutes_by_week` ist bei Freelancern und Dienstleistern `null`.
 *
 * Darf die fragende Person die Stunden der Person nicht sehen (fremde Person ohne
 * „Stundenkonten einsehen"), sind `contract`, `planned`, `target_minutes_by_week` und `remaining`
 * `null` und `rules` leer. Abwesenheiten bleiben.
 */
class AiPersonWorkloadData extends Data
{
    /**
     * @param AiShiftRuleData[] $rules
     * @param AiWeekTargetData[] $target_minutes_by_week
     * @param AiWorkloadAbsenceData[] $absences
     */
    public function __construct(
        public AiPersonData $person,
        public ?AiContractRefData $contract,
        public array $rules,
        public ?AiPlannedMinutesData $planned,
        public ?array $target_minutes_by_week,
        public array $absences,
        public ?AiRemainingData $remaining,
    ) {
    }
}
