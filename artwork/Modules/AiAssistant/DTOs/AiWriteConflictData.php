<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Konflikt an einem Vorschau-Eintrag (core-write-endpoints-dry-run; Konzept W1 §2).
 *
 * `type`: `room_collision` (der Raum ist in der Zeit schon belegt) oder `missing_permission`
 * (die fragende Person darf dieses Objekt nicht anlegen/aendern/loeschen).
 *
 * Schichtzuweisungen (`assign_shifts`, ai-shift-planning) kennen zusaetzlich `overlap`, `vacation`,
 * `unavailable`, `rule:<trigger_type>`, `not_qualified`, `already_assigned`, `no_open_demand`,
 * `cannot_work_shifts`; `unassign_shifts` meldet `demand_opens` (Hinweis: danach ist Bedarf offen),
 * `update_event` meldet `shifts_not_extended` (Hinweis: Schichten am Termin bleiben, wie sie sind).
 * `event` ist dort der Termin der Schicht.
 *
 * ai-scenarios-4: `create_absence` meldet `has_shifts` und `already_absent` (Hinweise),
 * `update_events`/`update_event` `shifts_moved` bzw. `shifts_not_moved` (Hinweise) und `invalid_move`
 * (Blocker, 422), `create_task` `new_checklist`/`added_to_project_team` (Hinweise), `copy_shifts`
 * `assignment_skipped` (die Zuweisung wird nicht mitkopiert; danach folgen die Gruende),
 * `replace_in_shifts` zusaetzlich `not_assigned` (Blocker).
 */
class AiWriteConflictData extends Data
{
    public const ROOM_COLLISION = 'room_collision';

    public const MISSING_PERMISSION = 'missing_permission';

    public const NOT_QUALIFIED = 'not_qualified';

    public const ALREADY_ASSIGNED = 'already_assigned';

    public const NO_OPEN_DEMAND = 'no_open_demand';

    public const CANNOT_WORK_SHIFTS = 'cannot_work_shifts';

    public const DEMAND_OPENS = 'demand_opens';

    public const NOT_ASSIGNED = 'not_assigned';

    public const SHIFTS_NOT_EXTENDED = 'shifts_not_extended';

    public const HAS_SHIFTS = 'has_shifts';

    public const ALREADY_ABSENT = 'already_absent';

    public const SHIFTS_MOVED = 'shifts_moved';

    public const SHIFTS_NOT_MOVED = 'shifts_not_moved';

    public const INVALID_MOVE = 'invalid_move';

    public const NEW_CHECKLIST = 'new_checklist';

    public const ADDED_TO_PROJECT_TEAM = 'added_to_project_team';

    public const ASSIGNMENT_SKIPPED = 'assignment_skipped';

    public function __construct(
        public string $type,
        public string $message,
        public ?AiRefData $event,
    ) {
    }
}
