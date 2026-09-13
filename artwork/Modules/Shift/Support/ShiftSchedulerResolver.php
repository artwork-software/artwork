<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;

/**
 * Ermittelt, wer eine Person einer Schicht zugewiesen hat — und aus welcher
 * Quelle diese Angabe stammt.
 *
 * Hintergrund: Der Konflikthinweis ("… hat dich am … eingeplant, entgegen
 * deines ursprünglichen Eintrags") nannte ersatzweise den Festschreibenden,
 * wenn die Zuweisung selbst keinen Urheber gespeichert hatte (Zuweisungen von
 * vor shift_workers.assigned_by_user_id). Das las sich, als hätte die
 * festschreibende Person die Einteilung vorgenommen — im Schichtverlauf war
 * davon nichts zu finden, weil sie die Schicht nur festgeschrieben hat.
 * Die Quelle wird deshalb mitgeführt, damit der Hinweis sagen kann, was die
 * genannte Person tatsächlich getan hat.
 */
final class ShiftSchedulerResolver
{
    public const SOURCE_ASSIGNED = 'assigned';
    public const SOURCE_COMMITTED = 'committed';

    /**
     * @return array{name: ?string, source: ?string, at: ?string}
     *         name   – Anzeigename, source – assigned|committed|null,
     *         at     – Zeitpunkt der Zuweisung (Y-m-d H:i:s) soweit bekannt
     */
    public static function resolve(Shift $shift, User|Freelancer|ServiceProvider|null $worker): array
    {
        $pivot = $worker !== null
            ? ShiftWorker::byEmployableIdAndShiftId(self::employableType($worker), $worker->id, $shift->id)->first()
            : null;

        // Zeitpunkt der Zuweisung kennen wir auch dann, wenn der Urheber fehlt —
        // er macht den Hinweis für Altzuweisungen überhaupt erst nachvollziehbar.
        $assignedAt = $pivot?->created_at?->format('Y-m-d H:i:s');

        $assignedBy = $pivot?->assignedBy;
        if ($assignedBy !== null) {
            return [
                'name' => $assignedBy->full_name,
                'source' => self::SOURCE_ASSIGNED,
                'at' => $assignedAt,
            ];
        }

        $committedBy = $shift->committedBy()->first();

        return [
            'name' => $committedBy?->full_name,
            'source' => $committedBy !== null ? self::SOURCE_COMMITTED : null,
            'at' => $assignedAt,
        ];
    }

    /**
     * Basis-FQCN wie in shift_workers.employable_type abgelegt.
     */
    private static function employableType(User|Freelancer|ServiceProvider $worker): string
    {
        return match (true) {
            $worker instanceof User => User::class,
            $worker instanceof Freelancer => Freelancer::class,
            default => ServiceProvider::class,
        };
    }
}
