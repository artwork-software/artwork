<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Einziger Weg, eine bestehende Schicht zu bearbeiten (Dienstplan/Projekt im Web und App-API): Speichern,
 * Entfernen der Besetzung bei Gewerkwechsel, Schichtplätze, danach Projekt-Tageszuordnungen, Regel- und
 * Konflikt-Neuprüfung, Benachrichtigung + Verlauf bei festgeschriebenen Schichten und Live-Update.
 * Gewerks-Scoping (CraftScopeService) und Validierung bleiben beim Aufrufer.
 */
class ShiftUpdateService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftWorkerService $shiftWorkerService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly AvailabilityConflictService $availabilityConflictService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes Schichtfelder (start_date, end_date, start, end, break_minutes,
     *     craft_id, description, project_id, shift_group_id, room_id, …)
     * @param array<int, array{shift_qualification_id: int, value: int|null}> $shiftsQualifications
     *     Schichtplätze, die gesetzt werden (Werte unter der Besetzung werden hochgezogen)
     * @param bool $clearStaffing Alle Schichtplätze samt Besetzung entfernen (Web: bewusst leer geschickt)
     * @param SupportCollection<int, mixed>|null $globalQualifications
     * @return UpdateEventShiftInShiftPlan|UpdateShiftInShiftPlan|null Gesendetes Live-Update (für die
     *     Antwort an die speichernde Ansicht)
     */
    public function update(
        Shift $shift,
        array $attributes,
        array $shiftsQualifications = [],
        bool $clearStaffing = false,
        ?SupportCollection $globalQualifications = null,
    ): UpdateEventShiftInShiftPlan|UpdateShiftInShiftPlan|null {
        $previousRoomId = $shift->room_id;
        // Stand vor der Änderung: für Benachrichtigung (nur bei echter Änderung) und Neubewertung
        $before = [
            'project_id' => $shift->project_id,
            'start_date' => Carbon::parse($shift->start_date)->toDateString(),
            'end_date' => Carbon::parse($shift->end_date ?? $shift->start_date)->toDateString(),
            'slots' => $this->shiftSlotSnapshot($shift),
            'users' => $shift->users()->get(),
        ];
        $changedFields = [];

        // Mutations-Teil atomar: Save, Worker-Entfernung und Qualifikations-Updates
        // gehören zusammen — bricht ein Schritt ab, bleibt kein halber Zustand zurück.
        DB::transaction(function () use (
            $shift,
            $attributes,
            $shiftsQualifications,
            $clearStaffing,
            $globalQualifications,
            &$changedFields
        ): void {
            $shift->fill($attributes);

            $craftChanged = $shift->isDirty('craft_id');

            $this->shiftService->save($shift);
            $changedFields = array_values(array_diff(array_keys($shift->getChanges()), ['updated_at']));

            // When the craft changes, remove all assigned workers since they may not
            // be qualified for the new craft, and reload the craft relation for the broadcast.
            // Über den Service-Pfad statt Bulk-forceDelete: nur so laufen Benachrichtigung
            // der Entfernten, Änderungs-Verlauf, shift_count-Recalc und Cache-Invalidierung.
            if ($craftChanged) {
                $this->removeAllWorkersFromShift($shift);
                $this->forceDeleteLegacyPivots($shift);

                $shift->unsetRelation('users');
                $shift->unsetRelation('freelancer');
                $shift->unsetRelation('serviceProvider');
                $shift->load('craft:id,name,abbreviation,color');
            }

            // Nur löschen, wenn bewusst alle Schichtplätze entfernt wurden. Bei partiellen Updates (z. B.
            // zeitliches Verschieben) dürfen weder Schichtplätze noch Zuweisungen gelöscht werden.
            if ($clearStaffing) {
                $this->removeAllWorkersFromShift($shift);
                $this->forceDeleteLegacyPivots($shift);

                // Model-Deletes statt Bulk-Query: nur so feuert der Observer und die
                // entfernten Schichtplätze erscheinen im Schichtverlauf.
                $shift->shiftsQualifications()->get()->each(
                    static fn ($shiftsQualification) => $shiftsQualification->delete()
                );

                $shift->unsetRelation('users');
            }

            foreach ($shiftsQualifications as $shiftsQualification) {
                $this->shiftsQualificationsService->updateShiftsQualificationForShift(
                    $shift->id,
                    $shiftsQualification
                );
            }

            $this->shiftService->handleGlobalQualificationChange($globalQualifications ?? collect(), $shift);
        });

        $this->afterShiftUpdated($shift, $before, $changedFields);

        $shiftPlanUpdate = null;
        if ($shift->event_id) {
            if ($shift->event?->room_id !== null) {
                $shiftPlanUpdate = new UpdateEventShiftInShiftPlan($shift, $shift->event->room_id);
            }
        } elseif ($shift->room_id !== null) {
            $shiftPlanUpdate = new UpdateShiftInShiftPlan($shift, $shift->room_id, $previousRoomId);
        }
        if ($shiftPlanUpdate !== null) {
            broadcast($shiftPlanUpdate);
        }

        return $shiftPlanUpdate;
    }

    /**
     * Re-run the shift-rule checks for the given users over the given date range so that
     * violations (e.g. HFT/shift conflicts, rest time) surface immediately after a mutation.
     *
     * @param iterable<mixed> $users
     */
    public function revalidateShiftRules(iterable $users, Carbon $start, Carbon $end): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->shiftRuleService->validateRulesForUser($user, $start->copy(), $end->copy());
            }
        }
    }

    /**
     * @return array{slots: array<int, int>, global: array<int, int>} Schichtplätze und globale Qualifikationen je ID
     */
    public function shiftSlotSnapshot(Shift $shift): array
    {
        return [
            'slots' => $shift->shiftsQualifications()->pluck('value', 'shift_qualification_id')->all(),
            'global' => $shift->globalQualifications()->pluck('quantity', 'global_qualifications.id')->all(),
        ];
    }

    /**
     * Folgen einer Schichtänderung: Projekt-Tageszuordnungen bei Projekt-/Datumswechsel umhängen, Regeln
     * und (bei festgeschriebenen Schichten) Urlaubs-/Verfügbarkeitskonflikte neu bewerten, Benachrichtigung
     * nur bei echter Änderung.
     *
     * @param array{project_id: ?int, start_date: string, end_date: string, slots: array<string, mixed>,
     *     users: Collection<int, User>} $before
     * @param array<int, string> $changedFields
     */
    private function afterShiftUpdated(Shift $shift, array $before, array $changedFields): void
    {
        $shift->refresh();
        $afterStart = Carbon::parse($shift->start_date)->toDateString();
        $afterEnd = Carbon::parse($shift->end_date ?? $shift->start_date)->toDateString();

        $scopeChanged = $before['project_id'] !== $shift->project_id
            || $before['start_date'] !== $afterStart
            || $before['end_date'] !== $afterEnd;
        $scheduleChanged = array_intersect(
            $changedFields,
            ['start_date', 'end_date', 'start', 'end', 'break_minutes', 'craft_id']
        ) !== [];
        $slotsChanged = $before['slots'] != $this->shiftSlotSnapshot($shift);

        if ($scopeChanged) {
            $this->shiftService->resyncProjectDayAssignments($shift);
        }

        if ($scheduleChanged) {
            // Alte UND neue Besetzung (Gewerkwechsel entfernt Personen) über alten UND neuen Zeitraum
            $users = $before['users']->concat($shift->users()->get())->unique('id');
            $this->revalidateShiftRules(
                $users,
                Carbon::parse(min($before['start_date'], $afterStart)),
                Carbon::parse(max($before['end_date'], $afterEnd))
            );

            if ($shift->is_committed) {
                $this->shiftService->recheckAvailabilityConflicts($shift);
            }
        }

        if ($shift->is_committed && ($changedFields !== [] || $slotsChanged)) {
            $this->notifyCommittedShiftChanged($shift);
        }
    }

    /**
     * Benachrichtigung + Verlauf bei geänderter festgeschriebener Schicht. Läuft NACH dem Speichern
     * und an die Besetzung bzw. Planer:innen des aktuellen Gewerks.
     */
    private function notifyCommittedShiftChanged(Shift $shift): void
    {
        $event = $shift->event;

        if ($event?->exists) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Shift of event has been edited')
                    ->setTranslationKeyPlaceholderValues([$event->eventName])
            );
        }

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

        foreach ($shift->users()->get() as $user) {
            $this->notifyAboutCommittedChange($shift, $user);
        }

        // Nur Planer:innen des Gewerks (Fallback: Gewerksverantwortliche) — nicht alle
        // Gewerksmitglieder; bereits benachrichtigte Schichtbesetzung wird ausgelassen.
        $notifiedUserIds = $shift->users()->pluck('users.id')->all();

        /** @var User $craftUser */
        foreach ($this->craftPlannersToNotify($shift->craft()->first(), $notifiedUserIds) as $craftUser) {
            if (Auth::id() !== $craftUser->id) {
                $this->notifyAboutCommittedChange($shift, $craftUser);
            }
        }
    }

    private function notifyAboutCommittedChange(Shift $shift, User $user): void
    {
        $notificationTitle = __(
            'notification.shift.locked_changes',
            [
                'projectName' => $shift->project?->name
                    ?? $shift->event?->project?->name
                    ?? __('notification.shift.without_project'),
                'craftAbbreviation' => $shift->craft->abbreviation
            ],
            $user->language
        );
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => __('notification.keyWords.concerns_shift', [], $user->language) .
                    $shift->time_span_label,
                'href' => null
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    /**
     * Planer:innen des Gewerks; ohne eingetragene Planer:innen die Gewerksverantwortlichen.
     *
     * @param array<int, int> $excludeUserIds
     * @return Collection<int, User>
     */
    private function craftPlannersToNotify(?Craft $craft, array $excludeUserIds = []): Collection
    {
        if ($craft === null) {
            return new Collection();
        }

        $planners = $craft->craftShiftPlaner()->get();
        if ($planners->isEmpty()) {
            $planners = $craft->managingUsers()->get();
        }

        return $planners
            ->reject(static fn (User $user): bool => in_array($user->id, $excludeUserIds, true))
            ->unique('id')
            ->values();
    }

    /**
     * Entfernt alle Zuweisungen einer Schicht über ShiftWorkerService::removeFromShift — damit laufen
     * Benachrichtigung der Entfernten (bei festgeschriebenen Schichten), Änderungs-Verlauf,
     * shift_count-Recalc und Working-Hour-Cache-Invalidierung.
     */
    private function removeAllWorkersFromShift(Shift $shift): void
    {
        ShiftWorker::where('shift_id', $shift->id)->get()->each(
            fn (ShiftWorker $pivot) => $this->shiftWorkerService->removeFromShift(
                $pivot,
                true,
                $this->notificationService,
                $this->vacationConflictService,
                $this->availabilityConflictService,
                $this->changeService
            )
        );
    }

    /**
     * Legacy-Pivots (werden vom unified Pfad nicht mehr befüllt) aufräumen.
     */
    private function forceDeleteLegacyPivots(Shift $shift): void
    {
        ShiftUser::where('shift_id', $shift->id)->forceDelete();
        ShiftFreelancer::where('shift_id', $shift->id)->forceDelete();
        ShiftServiceProvider::where('shift_id', $shift->id)->forceDelete();
    }
}
