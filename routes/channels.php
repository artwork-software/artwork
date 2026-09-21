<?php

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| Kanäle, die Schichtdaten oder Projektinhalte transportieren, prüfen das jeweilige
| Sichtrecht; Auth::check() reicht nur für reine Reload-Signale.
|
*/

// Schichtplan-Sichtrecht: Dienstplan-Betrachter und -Planer (Admins via Gate::before)
$canViewShiftPlan = static fn ($user): bool => $user instanceof User
    && (
        $user->can(PermissionEnum::VIEW_SHIFT_PLAN->value)
        || $user->can(PermissionEnum::SHIFT_PLANNER->value)
    );

// Reload-Signale ohne Payload (OccupancyUpdated, PushesEventModification: roomId + Datumsbereich)
Broadcast::channel('events', function () {
    return Auth::check();
});

// Reload-Signal ohne Payload (UserUpdated)
Broadcast::channel('users', function () {
    return Auth::check();
});

// Reload-Signal ohne Payload (ProjectUpdated)
Broadcast::channel('projects', function () {
    return Auth::check();
});

// Reload-Signal ohne Payload (DepartmentUpdated)
Broadcast::channel('departments', function () {
    return Auth::check();
});

// ShiftAssigned: Schichtdaten mit Zuweisungen
Broadcast::channel('shifts', function ($user) use ($canViewShiftPlan) {
    return $canViewShiftPlan($user);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tagesbemerkungen: nur User mit Sichtrecht (Admins via Gate::before)
Broadcast::channel('day-remarks', function ($user) {
    return $user instanceof User
        && (
            $user->can(PermissionEnum::DAY_REMARKS_VIEW->value)
            || $user->can(PermissionEnum::DAY_REMARKS_EDIT->value)
        );
});


Broadcast::channel('room.{roomId}.day.{dayString}', function ($user, $roomId, $dayString): void {
});


// ShiftDTOs (UpdateShiftInShiftPlan, AssignUserToShift, RemoveEntityFormShiftEvent, …)
Broadcast::channel('shift-plan.room.{roomId}', function ($user, $roomId) use ($canViewShiftPlan) {
    return $canViewShiftPlan($user);
});

// DestroyShift: ShiftDTO inkl. betroffener Personen
Broadcast::channel('destroy.events.room.{roomId}', function ($user, $roomId) use ($canViewShiftPlan) {
    return $canViewShiftPlan($user);
});

Broadcast::channel('shift-plan.shift.{shiftId}', function ($user, $shiftId) use ($canViewShiftPlan) {
    return $canViewShiftPlan($user);
});

// MultiShiftCreateInShiftPlan: Schichten inkl. Mitarbeitenden und Qualifikationen
Broadcast::channel('shift-plan.multi-shifts', function ($user) use ($canViewShiftPlan) {
    return $canViewShiftPlan($user);
});

// IndividualTimeChanged / WorkerAvailabilityChanged: nur workerId + workerType (Reload-Signal),
// Konsument ist ausschließlich der Dienstplan/Kalender-Listener
Broadcast::channel('shift-plan.individual-times', function () {
    return Auth::check();
});

Broadcast::channel('shift-plan.worker-availability', function () {
    return Auth::check();
});

// BulkEventChanged: Termin-Payload der Bulk-Bearbeitung (kein Personen-/Gehaltsbezug)
Broadcast::channel('bulk.events', function () {
    return Auth::check();
});

// Online-Status (UserStatusUpdated: userId + status; UserWentOffline: user_id)
Broadcast::channel('users.status', function () {
    return Auth::check();
});

Broadcast::channel('user-status', function () {
    return Auth::check();
});

// Termin-DTOs. Der Kalender steht jedem angemeldeten Nutzer offen und eine raumbezogene Sichtbarkeit
// gibt es nicht (RoomPolicy::view ist das Verwaltungsrecht): Auth::check() plus Existenz des Raums.
Broadcast::channel('event.room.{roomId}', function ($user, $roomId) {
    return $user instanceof User
        && Room::query()->whereKey((int) $roomId)->exists();
});

// Projektinhalte (Kommentare, Komponentenwerte, Dokument-Metadaten): Projekt-Sichtrecht
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    if (!$user instanceof User) {
        return false;
    }

    $project = Project::query()->find((int) $projectId);

    return $project !== null && $user->can('view', $project);
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return Chat::where('id', $chatId)
        ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('event-verification-index.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Reload-Signal ohne Payload (CrmSettingsChanged)
Broadcast::channel('crm.settings', function () {
    return Auth::check();
});
