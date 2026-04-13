<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Http\Requests\CrmImportExecuteRequest;
use Artwork\Modules\Crm\Http\Requests\CrmImportUploadRequest;
use Artwork\Modules\Crm\Services\CrmImportService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CrmImportController extends Controller
{
    public function __construct(
        private readonly CrmImportService $importService,
    ) {
    }

    public function showUpload(): Response
    {
        abort_unless(auth()->user()->can(PermissionEnum::CRM_MANAGER->value), 403);

        return Inertia::render('CRM/Import/Upload', [
            'contactTypes' => $this->importService->getImportableContactTypes(),
        ]);
    }

    public function upload(CrmImportUploadRequest $request): Response
    {
        $result = $this->importService->storeAndParseUpload($request->file('file'));

        if (!$result) {
            return Inertia::render('CRM/Import/Upload', [
                'contactTypes' => $this->importService->getImportableContactTypes(),
                'error' => __('The uploaded file contains no data.'),
            ]);
        }

        $contactType = $this->importService->loadContactTypeForMapping(
            $request->validated('crm_contact_type_id')
        );

        $this->importService->storeSession($result['path'], $contactType->id);

        return Inertia::render('CRM/Import/Mapping', [
            'contactType' => $contactType,
            'headers' => $result['parsed']['headers'],
            'preview' => $result['parsed']['preview'],
            'totalRows' => $result['parsed']['totalRows'],
        ]);
    }

    public function execute(CrmImportExecuteRequest $request): RedirectResponse
    {
        $sessionData = $this->importService->getSession();

        if (!$sessionData) {
            return redirect()->route('crm.index')
                ->with('error', __('Import session expired. Please start again.'));
        }

        $slug = $this->importService->getSessionContactTypeSlug();
        $result = $this->importService->runImport($request->validated('mapping'));

        return redirect()
            ->route('crm.index', ['type' => $slug])
            ->with('importResult', $result);
    }

    public function cancel(): RedirectResponse
    {
        $this->importService->cleanup();

        return redirect()->route('crm.index');
    }
}
