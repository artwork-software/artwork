<?php

namespace App\Http\Controllers;

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

    public function updateValue(Shift $shift, Request $request): void
    {
        $this->shiftsQualificationsService
            ->increaseValueOrCreateWithOneByQualification($shift->id, $request->integer('qualification_id'));

        broadcast(new \Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan($shift, $shift->room_id ?? $shift->event->room_id));
    }

    public function destroy(
        ShiftQualification $shiftQualification,
    ) {
        if (($shiftQualificationIdToDelete = $shiftQualification->getAttribute('id')) === 1) {
            return $this->redirector->back();
        }

        try {
            $shiftsQualificationsToHandle = $this->shiftsQualificationsService->findAllByShiftQualificationId(
                $shiftQualificationIdToDelete
            );

            /** @var ShiftsQualifications $shiftsQualification */
            foreach ($shiftsQualificationsToHandle as $shiftsQualification) {
                /** @var Shift $shiftByQualification */
                $shiftByQualification = $shiftsQualification
                    ->shift()
                    ->first();

                $shiftHasDefaultQualification = $shiftByQualification
                        ->shiftsQualifications()
                        ->where('shift_qualification_id', 1)
                        ->count() > 0;

                if (!$shiftHasDefaultQualification) {
                    $this->shiftsQualificationsService->createShiftsQualificationForShift(
                        $shiftByQualification->getAttribute('id'),
                        [
                            'shift_qualification_id' => 1,
                            'value' => 1
                        ]
                    );
                }

                $this->shiftWorkerService->updateShiftWorkerQualificationToDefault(
                    $shiftByQualification,
                    $shiftQualificationIdToDelete,
                );

                $this->shiftsQualificationsService->increaseValueOrCreateWithOne(
                    $shiftByQualification->getAttribute('id'),
                    1
                );

                $this->shiftsQualificationsService->forceDelete($shiftsQualification);
            }

            $this->shiftQualificationService->delete($shiftQualification);
        } catch (Throwable $t) {

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
