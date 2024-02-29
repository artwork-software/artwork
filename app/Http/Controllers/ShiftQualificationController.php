<?php

namespace App\Http\Controllers;

use Artwork\Modules\ShiftQualification\Http\Requests\StoreShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class ShiftQualificationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ShiftQualification::class, 'shift_qualification');
    }

    public function store(
        StoreShiftQualificationRequest $storeShiftQualificationRequest,
        ShiftQualificationService $shiftQualificationService
    ): RedirectResponse {
        try {
            $shiftQualificationService->createFromRequest($storeShiftQualificationRequest);
        } catch (Throwable $t) {
            Log::error($t->getMessage());

            return Redirect::back()->with(
                'error',
                ['shift_qualification' => __('flash-messages.shift-qualification.error.create')]
            );
        }

        return Redirect::back()->with(
            'success',
            ['shift_qualification' => __('flash-messages.shift-qualification.success.create')]
        );
    }

    public function update(
        UpdateShiftQualificationRequest $updateShiftQualificationRequest,
        ShiftQualification $shiftQualification,
        ShiftQualificationService $shiftQualificationService
    ): RedirectResponse {
        try {
            $shiftQualificationService->updateFromRequest($updateShiftQualificationRequest, $shiftQualification);
        } catch (Throwable $t) {
            Log::error($t->getMessage());

            return Redirect::back()->with(
                'error',
                ['shift_qualification' => __('flash-messages.shift-qualification.error.update')]
            );
        }

        return Redirect::back()->with(
            'success',
            ['shift_qualification' => __('flash-messages.shift-qualification.success.update')]
        );
    }
}
