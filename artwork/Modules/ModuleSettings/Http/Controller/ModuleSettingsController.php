<?php

namespace Artwork\Modules\ModuleSettings\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\ModuleSettings\Http\Requests\UpdateModuleSettingsRequest;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Inertia\Response;
use Inertia\ResponseFactory;

class ModuleSettingsController extends Controller
{
    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly Redirector $redirector,
        private readonly ResponseFactory $responseFactory
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ModuleSettings::class);

        return $this->responseFactory->render(
            'ModuleSettings/Index',
            [
                'moduleSettings' => $this->moduleSettingsService->getModuleSettings()
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateModuleSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', ModuleSettings::class);

        $this->moduleSettingsService->update(
            $request->string('menu'),
            $request->boolean('enabled')
        );

        return $this->redirector->back()->with(
            'success',
            __('flash-messages.module-settings.success.update')
        );
    }
}
