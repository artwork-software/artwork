<?php

namespace App\Http\Controllers;

use App\Console\Commands\GetSage100Data;
use App\Models\GeneralSettings;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ToolSettingsInterfacesController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(SageApiSettingsService $sageApiSettingsService): Response
    {
        $this->authorize('view', SageApiSettings::class);

        return Inertia::render(
            'Interfaces/Index',
            [
                'sageSettings' => $sageApiSettingsService->getFirst()
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function createOrUpdate(
        CreateOrUpdateSageApiSettingsRequest $createOrUpdateSageApiSettingsRequest,
        SageApiSettingsService $sageApiSettingsService
    ): RedirectResponse {
        $this->authorize('updateInterfaceSettings', SageApiSettings::class);

        try {
            $sageApiSettingsService->createOrUpdateFromRequest($createOrUpdateSageApiSettingsRequest);
        } catch (Throwable $t) {
            Log::error($t->getMessage());

            return Redirect::back()->with(
                'error',
                __('interfaces.failed_to_save')
            );
        }

        if (!$sageApiSettingsService->testConnection()) {
            return Redirect::back()->with(
                'error',
                __('interfaces.connection_test_failed')
            );
        }

        return Redirect::back()->with('success', __('interfaces.saved_successfully'));
    }

    public function initializeSage(): RedirectResponse
    {
        if (Artisan::call(GetSage100Data::class) === 0) {
            return Redirect::back()->with('success', __('interfaces.import_executed_successfully'));
        }

        return Redirect::back()->with('error', __('interfaces.import_executed_unsuccessfully'));
    }
}
