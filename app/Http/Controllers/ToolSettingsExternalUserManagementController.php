<?php

namespace App\Http\Controllers;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Role\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
    public function index(Request $request): Response
    {
        $this->authorize('view', GeneralSettings::class);

        $sources = $this->externalUserSourceService->getAllWithRelations();

        return Inertia::render('ExternalUserManagement/Index', [
            'sources' => $sources,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            // Anlegen/Ändern/Löschen von Quellen nur für Admins (ExternalUserSourcePolicy::manage).
            'canManageSources' => (bool) $request->user()?->can('manage', ExternalUserSource::class),
        ]);
    }
}
