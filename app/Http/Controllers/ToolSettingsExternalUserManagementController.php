<?php

namespace App\Http\Controllers;

use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class ToolSettingsExternalUserManagementController extends Controller
{
    public function __construct(
        private readonly ExternalUserSourceService $externalUserSourceService
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        $sources = $this->externalUserSourceService->getAllWithRelations();

        return Inertia::render('ExternalUserManagement/Index', [
            'sources' => $sources,
        ]);
    }
}

