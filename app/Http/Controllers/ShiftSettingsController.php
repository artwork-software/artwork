<?php

namespace App\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Services\ShiftSettingsPermissionService;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Models\ShiftTimePreset;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Response;
use Inertia\ResponseFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class ShiftSettingsController extends Controller
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly ResponseFactory $responseFactory,
        protected GlobalQualificationService $globalQualificationService,
        protected GeneralSettingsService $generalSettingsService,
    ) {
    }

    public function index(ShiftQualificationService $shiftQualificationService, ShiftSettings $shiftSettings): Response
    {
        return $this->responseFactory->render('Settings/ShiftSettings', [
            'crafts' => Craft::query()
                ->with('managingUsers', 'managingFreelancers', 'managingServiceProviders', 'qualifications')
                ->orderBy('position')
                ->get(),
            'eventTypes' => EventType::all(),
            'usersWithPermission' => User::permission(PermissionEnum::SHIFT_PLANNER->value)->get(),
            'usersWithInventoryPermission' => User::permission(PermissionEnum::INVENTORY_PLANER->value)->get(),
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByPosition(),
            'shiftTimePresets' => ShiftTimePreset::all(),
            'shiftSettings' => $shiftSettings,
            'shiftCommitWorkflowUsers' => ShiftCommitWorkflowUser::with('user')
                ->orderBy('user_id')
                ->get(),
            'globalQualifications' => $this->globalQualificationService->getAll(),
        ]);
    }

    public function updateShiftSettingsUseFirstNameForSort(
        Request $request,
        ShiftSettings $shiftSettings,
        LoggerInterface $logger
    ): RedirectResponse {
        try {
            $shiftSettings->use_first_name_for_sort = $request->boolean('use_first_name_for_sort');
            $shiftSettings->save();
        } catch (Throwable $t) {
            $logger->error('Failed to save shift setting use_first_name_for_sort: ' . $t->getMessage());

            return $this->redirector->back()->with(
                'error',
                ['shift_settings' => __('flash-messages.shift-settings.error.update')]
            );
        }

        return $this->redirector->back();
    }


    public function updateCalendarAboShowAllShifts(
        Request $request,
        ShiftSettings $shiftSettings,
        LoggerInterface $logger
    ): RedirectResponse {
        try {
            $shiftSettings->calendar_abo_show_all_shifts = $request->boolean('calendar_abo_show_all_shifts');
            $shiftSettings->save();
        } catch (Throwable $t) {
            $logger->error('Failed to save shift setting calendar_abo_show_all_shifts: ' . $t->getMessage());

            return $this->redirector->back()->with(
                'error',
                ['shift_settings' => __('flash-messages.shift-settings.error.update')]
            );
        }

        return $this->redirector->back();
    }

    public function updateAllowShiftOverbooking(
        Request $request,
        ShiftSettings $shiftSettings,
        LoggerInterface $logger
    ): RedirectResponse {
        try {
            $shiftSettings->allow_shift_overbooking = $request->boolean('allow_shift_overbooking');
            $shiftSettings->save();
        } catch (Throwable $t) {
            $logger->error('Failed to save shift setting allow_shift_overbooking: ' . $t->getMessage());

            return $this->redirector->back()->with(
                'error',
                ['shift_settings' => __('flash-messages.shift-settings.error.update')]
            );
        }

        return $this->redirector->back();
    }

    public function updateGranularPermissions(
        Request $request,
        ShiftSettings $shiftSettings,
        ShiftSettingsPermissionService $permissionService
    ): RedirectResponse {
        $enabled = $request->boolean('granular_permissions_enabled');

        // Defaults nur EINMALIG verteilen. Sonst würde ein erneutes Off→On
        // individuell entzogene granulare Rechte wieder zurückbringen.
        if ($enabled && !$shiftSettings->granular_defaults_granted) {
            $permissionService->grantGranularDefaultsToMasterPermissionHolders();
            $shiftSettings->granular_defaults_granted = true;
        }

        $shiftSettings->granular_permissions_enabled = $enabled;
        $shiftSettings->save();

        return $this->redirector->back();
    }

    public function updateOwnRosterUncommittedShiftVisibility(
        Request $request,
        ShiftSettings $shiftSettings
    ): RedirectResponse {
        $shiftSettings->hide_uncommitted_shifts_from_own_roster = $request->boolean(
            'hide_uncommitted_shifts_from_own_roster'
        );
        $shiftSettings->save();

        return $this->redirector->back();
    }

    public function updateShiftConfirmationSettings(
        Request $request,
        ShiftSettings $shiftSettings
    ): RedirectResponse {
        $shiftSettings->shift_confirmation_enabled = $request->boolean('shift_confirmation_enabled');
        $shiftSettings->shift_confirmation_in_history = $request->boolean('shift_confirmation_in_history');
        $shiftSettings->save();

        return $this->redirector->back();
    }

    public function saveWarningMultipleAssignments(Request $request): void
    {
        $this->generalSettingsService->updateWarningMultipleAssignmentsFromRequest($request);
    }
}
