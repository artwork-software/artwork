<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalUserManagement\Http\Requests\StoreExternalUserGroupMappingRequest;
use Artwork\Modules\ExternalUserManagement\Http\Requests\UpdateExternalUserGroupMappingRequest;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ExternalUserGroupMappingController extends Controller
{
    public function __construct(
        private readonly ExternalUserGroupMappingService $externalUserGroupMappingService
    ) {
    }

    public function index(Request $request, int $sourceId): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $mappings = $this->externalUserGroupMappingService->getAllBySourceId($sourceId);

        return response()->json($mappings);
    }

    public function store(StoreExternalUserGroupMappingRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $mapping = $this->externalUserGroupMappingService->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($mapping, 201);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_group_mapping.success.create'));
    }

    public function update(
        UpdateExternalUserGroupMappingRequest $request,
        ExternalUserGroupMapping $externalUserGroupMapping
    ): RedirectResponse|JsonResponse {
        $this->authorize('view', GeneralSettings::class);

        $mapping = $this->externalUserGroupMappingService->update($externalUserGroupMapping, $request->validated());

        if ($request->wantsJson()) {
            return response()->json($mapping);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_group_mapping.success.update'));
    }

    public function destroy(ExternalUserGroupMapping $externalUserGroupMapping): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $this->externalUserGroupMappingService->delete($externalUserGroupMapping);

        if (request()->wantsJson()) {
            return response()->json(['message' => __('flash-messages.external_user_group_mapping.success.delete')]);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_group_mapping.success.delete'));
    }
}

