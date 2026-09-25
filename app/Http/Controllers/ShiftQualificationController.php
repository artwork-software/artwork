<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Http\Requests\StoreShiftQualificationRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftQualificationRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

class ShiftQualificationController extends Controller
{
    public function __construct(
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftQualificationService $shiftQualificationService,
        private readonly ShiftWorkerService $shiftWorkerService,
        private readonly Redirector $redirector,
        private readonly LoggerInterface $logger
    ) {
        $this->authorizeResource(ShiftQualification::class, 'shift_qualification');
    }

    public function store(
        StoreShiftQualificationRequest $storeShiftQualificationRequest,
    ): RedirectResponse {
        try {
            $this->shiftQualificationService->createFromRequest($storeShiftQualificationRequest);
        } catch (Throwable $t) {
            return $this->redirector->back()->with(
                'error',
                ['shift_qualification' => __('flash-messages.shift-qualification.error.create')]
            );
        }

        return $this->redirector->back()->with(
            'success',
            ['shift_qualification' => __('flash-messages.shift-qualification.success.create')]
        );
    }

    public function update(
        UpdateShiftQualificationRequest $updateShiftQualificationRequest,
        ShiftQualification $shiftQualification,
    ): RedirectResponse {
        try {
            $this->shiftQualificationService->updateFromRequest($updateShiftQualificationRequest, $shiftQualification);
        } catch (Throwable $t) {
            return $this->redirector->back()->with(
                'error',
                ['shift_qualification' => __('flash-messages.shift-qualification.error.update')]
            );
        }

        return $this->redirector->back()->with(
            'success',
            ['shift_qualification' => __('flash-messages.shift-qualification.success.update')]
        );
    }

    public function reorder(Request $request): RedirectResponse
    {
        // Autorisierung via Route-Middleware 'shift-settings-area:general,edit' —
        // die Admin-only ShiftQualificationPolicy würde Nicht-Admins mit Edit-Permission
        // (die das Drag&Drop in den Settings sehen) fälschlich mit 403 aussperren.

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:shift_qualifications,id'],
        ]);

        $this->shiftQualificationService->updateOrder($validated['ids']);

        return $this->redirector->back();
    }

    public function updateValue(Shift $shift, Request $request): void
    {
        app(CraftScopeService::class)->assertCanPlanShifts($request->user(), [$shift]);

        // Unbekannte qualification_id würde sonst als FK-Verletzung mit 500 enden
        $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
        ]);

        $this->shiftsQualificationsService
            ->increaseValueOrCreateWithOneByQualification($shift->id, $request->integer('qualification_id'));

        broadcast(new \Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan(
            $shift,
            $shift->room_id ?? $shift->event?->room_id
        ));
    }

    public function increaseOverbookedValue(Shift $shift, Request $request): void
    {
        app(CraftScopeService::class)->assertCanPlanShifts($request->user(), [$shift]);

        if (!app(\App\Settings\ShiftSettings::class)->allow_shift_overbooking) {
            abort(403, 'Shift overbooking is not enabled for this instance.');
        }

        $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
        ]);

        $this->shiftsQualificationsService
            ->increaseOverbookedValue($shift->id, $request->integer('qualification_id'));

        broadcast(new \Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan(
            $shift,
            $shift->room_id ?? $shift->event?->room_id
        ));
    }

    public function decreaseOverbookedValue(Shift $shift, Request $request): void
    {
        app(CraftScopeService::class)->assertCanPlanShifts($request->user(), [$shift]);

        if (!app(\App\Settings\ShiftSettings::class)->allow_shift_overbooking) {
            abort(403, 'Shift overbooking is not enabled for this instance.');
        }

        $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
        ]);

        $this->shiftsQualificationsService
            ->decreaseOverbookedValue($shift->id, $request->integer('qualification_id'));

        broadcast(new \Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan(
            $shift,
            $shift->room_id ?? $shift->event?->room_id
        ));
    }

    public function destroy(
        ShiftQualification $shiftQualification,
    ) {
        if (($shiftQualificationIdToDelete = $shiftQualification->getAttribute('id')) === 1) {
            return $this->redirector->back();
        }

        try {
            DB::transaction(function () use ($shiftQualification, $shiftQualificationIdToDelete): void {
                // Schichtplätze dieser Funktion (auch im Papierkorb) auf die Standard-Funktion (id 1)
                // übertragen — die Anzahl bleibt erhalten, die eingeplanten Personen wandern mit.
                $shiftsQualificationsToHandle = ShiftsQualifications::withTrashed()
                    ->where('shift_qualification_id', $shiftQualificationIdToDelete)
                    ->get();

                /** @var ShiftsQualifications $shiftsQualification */
                foreach ($shiftsQualificationsToHandle as $shiftsQualification) {
                    $defaultSlot = ShiftsQualifications::withTrashed()
                        ->where('shift_id', $shiftsQualification->shift_id)
                        ->where('shift_qualification_id', 1)
                        ->first();

                    if ($defaultSlot !== null) {
                        $defaultSlot->update([
                            'value' => (int) $defaultSlot->value + (int) $shiftsQualification->value,
                        ]);
                    } else {
                        ShiftsQualifications::query()->create([
                            'shift_id' => $shiftsQualification->shift_id,
                            'shift_qualification_id' => 1,
                            'value' => (int) $shiftsQualification->value,
                        ]);
                    }

                    $shiftsQualification->forceDelete();
                }

                // Zuweisungen (Source of Truth + Legacy-Pivots, inkl. Papierkorb) umhängen — die
                // Fremdschlüssel haben keine Löschregel und blockierten das Löschen sonst.
                foreach (['shift_workers', 'shift_user', 'shifts_freelancers', 'shifts_service_providers'] as $table) {
                    DB::table($table)
                        ->where('shift_qualification_id', $shiftQualificationIdToDelete)
                        ->update(['shift_qualification_id' => 1]);
                }
                DB::table('preset_shift_shifts_qualifications')
                    ->where('shift_qualification_id', $shiftQualificationIdToDelete)
                    ->delete();

                $this->shiftQualificationService->delete($shiftQualification);
            });
        } catch (Throwable $t) {
            $this->logger->error(
                'Failed to delete shift qualification ' . $shiftQualificationIdToDelete . ': ' . $t->getMessage()
            );

            return $this->redirector->back()->with(
                'error',
                ['shift_qualification' => __('flash-messages.shift-qualification.error.destroy')]
            );
        }

        return $this->redirector->back()->with(
            'success',
            ['shift_qualification' => __('flash-messages.shift-qualification.success.destroy')]
        );
    }
}
