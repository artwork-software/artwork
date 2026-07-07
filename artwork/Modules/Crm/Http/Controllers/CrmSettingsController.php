<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Crm\Services\CrmPropertyService;
use Inertia\Inertia;
use Inertia\Response;

class CrmSettingsController extends Controller
{
    public function __construct(
        private readonly CrmContactTypeService $contactTypeService,
        private readonly CrmPropertyGroupService $propertyGroupService,
        private readonly CrmPropertyService $propertyService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('CRM/Settings/Index', [
            'contactTypes' => $this->contactTypeService->getAllWithProperties(),
            'propertyGroups' => $this->propertyGroupService->getAll(),
        ]);
    }
}
