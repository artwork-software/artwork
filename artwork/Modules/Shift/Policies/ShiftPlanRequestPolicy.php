<?php

namespace Artwork\Modules\Shift\Policies;

use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftPlanRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Freigabe-Anfrage zurückziehen (DELETE shift-plan-requests/{id}):
     * Genehmiger*innen (Gate approve-shift-plan-requests, Admins via Gate::before) immer —
     * der Controller meldet bereits entschiedene Anfragen als Fehler zurück.
     * Antragsteller*innen nur für ihre eigene, noch offene (pending) Anfrage.
     */
    public function withdraw(User $user, ShiftPlanRequest $shiftPlanRequest): bool
    {
        if ($user->can('approve-shift-plan-requests')) {
            return true;
        }

        return (int) $shiftPlanRequest->requested_by_user_id === (int) $user->id
            && $shiftPlanRequest->status === 'pending';
    }
}
