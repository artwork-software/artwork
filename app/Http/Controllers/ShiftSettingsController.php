<?php

namespace App\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
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
    ) {
    }

    public function index(ShiftQualificationService $shiftQualificationService, ShiftSettings $shiftSettings): Response
    {
        return $this->responseFactory->render('Settings/ShiftSettings', [
            'crafts' => Craft::query()
                ->with('managingUsers', 'managingFreelancers', 'managingServiceProviders')
                ->orderBy('position')
                ->get(),
            'eventTypes' => EventType::all(),
            'usersWithPermission' => User::permission(PermissionEnum::SHIFT_PLANNER->value)->get(),
            'usersWithInventoryPermission' => User::permission(PermissionEnum::INVENTORY_PLANER->value)->get(),
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'shiftTimePresets' => ShiftTimePreset::all(),
            'shiftSettings' => $shiftSettings,
            'shiftCommitWorkflowUsers' => ShiftCommitWorkflowUser::with('user')
                ->orderBy('user_id')
                ->get()
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
        }

        return $this->redirector->back();
    }
}
