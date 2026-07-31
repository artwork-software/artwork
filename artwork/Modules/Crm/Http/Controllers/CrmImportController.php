<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Http\Requests\CrmImportExecuteRequest;
use Artwork\Modules\Crm\Http\Requests\CrmImportTypeMapRequest;
use Artwork\Modules\Crm\Http\Requests\CrmImportUploadRequest;
use Artwork\Modules\Crm\Services\CrmImportService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CrmImportController extends Controller
{
    public function __construct(
        private readonly CrmImportService $importService,
        private readonly CrmPropertyGroupService $propertyGroupService,
    ) {
    }

    public function showUpload(): Response
    {
        abort_unless(auth()->user()->can(PermissionEnum::CRM_MANAGER->value), 403);

        return Inertia::render('CRM/Import/Upload', [
            'contactTypes' => $this->importService->getImportableContactTypes(),
            'propertyGroups' => $this->propertyGroupService->getAll(),
        ]);
    }

    public function upload(CrmImportUploadRequest $request): Response
    {
        $result = $this->importService->storeAndParseUpload($request->file('file'));

        if (!$result) {
            return Inertia::render('CRM/Import/Upload', [
                'contactTypes' => $this->importService->getImportableContactTypes(),
                'propertyGroups' => $this->propertyGroupService->getAll(),
                'error' => __('The uploaded file contains no data.'),
            ]);
        }

        if ($request->boolean('use_type_column')) {
            $this->importService->storeSessionMultiType($result['path']);

            return Inertia::render('CRM/Import/TypeMapping', [
                'contactTypes' => $this->importService->getImportableContactTypes(),
                'headers' => $result['parsed']['headers'],
                'preview' => $result['parsed']['preview'],
                'totalRows' => $result['parsed']['totalRows'],
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

    public function mapTypes(CrmImportTypeMapRequest $request): Response
    {
        $sessionData = $this->importService->getSession();

        if (!$sessionData) {
            return Inertia::render('CRM/Import/Upload', [
                'contactTypes' => $this->importService->getImportableContactTypes(),
                'propertyGroups' => $this->propertyGroupService->getAll(),
                'error' => __('Import session expired. Please start again.'),
            ]);
        }

        $validated = $request->validated();

        $this->importService->storeTypeValueMapping(
            $validated['type_value_mapping'],
            $validated['type_column_index'],
        );

        // Collect the unique contact type IDs that were actually assigned
        $typeIds = collect($validated['type_value_mapping'])
            ->pluck('crm_contact_type_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $contactTypes = $this->importService->loadContactTypesForMultiMapping($typeIds);

        // Re-parse the file for headers/preview
        $fullPath = $sessionData['fullPath'];
        $parsed = $this->parseFileForResponse($fullPath);

        return Inertia::render('CRM/Import/MultiMapping', [
            'contactTypes' => $contactTypes,
            'headers' => $parsed['headers'],
            'preview' => $parsed['preview'],
            'totalRows' => $parsed['totalRows'],
            'typeColumnIndex' => $validated['type_column_index'],
            'typeValueMapping' => $validated['type_value_mapping'],
        ]);
    }

    public function columnValues(int $columnIndex): JsonResponse
    {
        abort_unless(auth()->user()->can(PermissionEnum::CRM_MANAGER->value), 403);

        $sessionData = $this->importService->getSession();

        if (!$sessionData) {
            return response()->json(['error' => 'No active import session'], 422);
        }

        $values = $this->importService->extractUniqueColumnValuesFromFile(
            $sessionData['path'],
            $columnIndex,
        );

        return response()->json(['values' => $values]);
    }

    public function execute(CrmImportExecuteRequest $request): RedirectResponse
    {
        $sessionData = $this->importService->getSession();

        if (!$sessionData) {
            return redirect()->route('crm.index')
                ->with('error', __('Import session expired. Please start again.'));
        }

        $duplicates = $request->validated('duplicates') ?? [];

        if ($request->has('type_mappings')) {
            $result = $this->importService->runMultiTypeImport($request->validated('type_mappings'), $duplicates);

            return redirect()
                ->route('crm.index')
                ->with('importResult', $result);
        }

        $slug = $this->importService->getSessionContactTypeSlug();
        $result = $this->importService->runImport($request->validated('mapping'), $duplicates);

        return redirect()
            ->route('crm.index', ['type' => $slug])
            ->with('importResult', $result);
    }

    public function cancel(): RedirectResponse
    {
        $this->importService->cleanup();

        return redirect()->route('crm.index');
    }

    private function parseFileForResponse(string $fullPath): array
    {
        $sheets = \Maatwebsite\Excel\Facades\Excel::toArray(new class {
        }, $fullPath);
        $rows = $sheets[0] ?? [];

        if (count($rows) === 0) {
            return ['headers' => [], 'preview' => [], 'totalRows' => 0];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);
        $dataRows = array_slice($rows, 1);

        $dataRows = array_values(array_filter($dataRows, function (array $row): bool {
            return collect($row)->contains(fn ($cell) => $cell !== null && trim((string) $cell) !== '');
        }));

        return [
            'headers' => $headers,
            'preview' => array_slice($dataRows, 0, 5),
            'totalRows' => count($dataRows),
        ];
    }
}
