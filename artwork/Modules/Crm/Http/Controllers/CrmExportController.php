<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Http\Requests\CrmExportRequest;
use Artwork\Modules\Crm\Services\CrmExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CrmExportController extends Controller
{
    public function __construct(
        private readonly CrmExportService $exportService,
    ) {
    }

    public function export(CrmExportRequest $request): BinaryFileResponse
    {
        return $this->exportService->export($request->validated());
    }
}
