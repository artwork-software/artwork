<?php

namespace Artwork\Modules\Event\Policies;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function __construct(private readonly EventSettingsService $eventSettingsService)
    {
    }

    public function create(User $user, ?Room $room = null): bool
    {
        if (
            $user->can(PermissionEnum::EVENT_REQUEST->value) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            $user->can(PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)
        ) {
            return true;
        }

        // Fallback: room-specific permissions (room admin or requestable-by).
        // Callers should pass the room explicitly; the request()-lookup remains
        // for legacy web call sites that authorize without arguments.
        if ($room === null && ($roomId = request()->get('roomId'))) {
            $room = Room::find($roomId);
        }

        if ($room !== null) {
            return $room->admins()->where('user_id', $user->id)->exists()
                || $room->requestableBy()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Wer den Kalender sehen darf, darf jeden Termin lesen; ein Projekt-Sichtrecht wird nicht verlangt,
     * auch bei privatem Projekt. Nur Planungstermine (is_planning) liegen hinter dem Planungskalender-Recht.
     */
    public function view(User $user, Event $event): bool
    {
        if (!$event->is_planning) {
            return true;
        }

        return $this->viewPlanning($user);
    }

    /**
     * Planungstermine (Planungskalender, Termin-Listen, Exporte) nur mit Planungskalender-Recht.
     */
    public function viewPlanning(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value,
            PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value,
        ]);
    }

    /**
     * Ob der User in diesem Raum direkt buchen (asOption=false) bzw. eine
     * Belegung anfragen (asOption=true) darf — von EventController::storeEvent
     * und der App-API gemeinsam genutzt; Admins via Gate::before.
     */
    public function book(User $user, Room $room, bool $asOption = false, bool $inPlanning = false): bool
    {
        // "Termine immer direkt buchbar": keine Raumanfragen – wer anlegen darf (create), bucht direkt.
        if ($this->eventSettingsService->alwaysDirectBooking()) {
            return true;
        }

        $isRoomAdmin = $room->admins()->where('user_id', $user->id)->exists();
        $hasGlobalCreate = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);

        if (!$asOption) {
            // Reguläre Termine über "Termine fest planen", geplante Termine NUR über
            // "Im Planungskalender fest planen" (getrennte Berechtigung, keine Implikation).
            $canPlanFixed = $inPlanning
                ? $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)
                : $hasGlobalCreate;

            return $canPlanFixed || $isRoomAdmin || $room->everyone_can_book;
        }

        return $hasGlobalCreate
            || $user->can(PermissionEnum::EVENT_REQUEST->value)
            || $isRoomAdmin
            || $room->requestableBy()->where('user_id', $user->id)->exists()
            || $room->everyone_can_book;
    }

    /**
     * Ob der User einen Termin ohne Raum anlegen darf; Kalender und Planungskalender sind getrennt
     * berechtigt. Admins via Gate::before.
     */
    public function bookWithoutRoom(User $user, bool $inPlanning = false): bool
    {
        return $inPlanning
            ? $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)
            : $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
    }

    public function update(User $user, Event $event): bool
    {
        // "Projektleitung sein" (management projects) gab hier bisher systemweites Bearbeiten aller Termine —
        // Widerspruch zur Beschreibung. Jetzt: Schreibrecht im Projekt des Termins (Konzept Nutzerrechte 6.2 A).
        return $this->canWriteProjectOf($user, $event) ||
            $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            $event->creator?->id === $user->id;
    }

    public function answerRoomRequest(User $user, Event $event): bool
    {
        if (!$event->occupancy_option || $event->room_id === null) {
            return false;
        }

        return $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            ($event->room?->user_id === $user->id && !$event->room->admins()->exists());
    }

    // "Termin absagen": entfernt den Termin aus dem Raum. Gilt anders als
    // answerRoomRequest auch für bereits bestätigte Belegungen, nicht nur
    // für offene Raumanfragen (occupancy_option).
    public function declineEvent(User $user, Event $event): bool
    {
        if ($event->room_id === null) {
            return false;
        }

        return $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            ($event->is_planning && $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)) ||
            $event->creator?->id === $user->id ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            ($event->room?->user_id === $user->id && !$event->room->admins()->exists());
    }

    /**
     * Schreibrecht am Termin ODER Dienstplanung: die Zeitleiste wird im Schichten-Tab auch von
     * Planer:innen ohne Projekt-Schreibrecht gepflegt.
     */
    public function editTimeline(User $user, Event $event): bool
    {
        return $user->can(PermissionEnum::SHIFT_PLANNER->value) || $this->update($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canWriteProjectOf($user, $event) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            ($event->is_planning && $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            $event->creator?->id === $user->id;
    }

    private function canWriteProjectOf(User $user, Event $event): bool
    {
        $project = $event->project;

        return $project !== null && $user->can('update', $project);
    }
}
