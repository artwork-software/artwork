<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ShiftSettings;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftWorkerConfirmationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftWorkerConfirmationController extends Controller
{
    public function update(
        Request $request,
        ShiftWorker $shiftWorker,
        ShiftSettings $shiftSettings,
        ShiftWorkerConfirmationService $confirmationService
    ): RedirectResponse {
        abort_unless($shiftSettings->shift_confirmation_enabled, 403);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([ShiftWorker::CONFIRMATION_ACCEPTED, ShiftWorker::CONFIRMATION_DECLINED]),
            ],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $shiftWorker->load('shift');
        abort_if($shiftWorker->shift === null, 404);
        // Zu-/Absagen gibt es nur für festgeschriebene Schichten — vorher
        // ist der Plan noch in Arbeit und wird gar nicht erst angefragt.
        abort_unless((bool) $shiftWorker->shift->is_committed, 403);

        /** @var User $user */
        $user = $request->user();
        $isSelf = $shiftWorker->employable_type === User::class
            && (int) $shiftWorker->employable_id === (int) $user->id;

        // Für andere (insb. Freelancer/Dienstleister ohne Login) dürfen nur
        // Planer:innen stellvertretend erfassen.
        if (!$isSelf) {
            abort_unless($user->can('can plan shifts'), 403);
        }

        $confirmationService->respond(
            $shiftWorker,
            $validated['status'],
            $validated['comment'] ?? null
        );

        $shift = $shiftWorker->shift;
        if (!$shift->event_id) {
            if ($shift->room_id) {
                broadcast(new UpdateShiftInShiftPlan($shift, $shift->room_id));
            }
        } elseif ($shift->event?->room_id) {
            broadcast(new UpdateShiftInShiftPlan($shift, $shift->event->room_id));
        }

        return back();
    }
}
