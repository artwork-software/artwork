<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalUserManagement\Http\Requests\StoreExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Http\Requests\UpdateExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ExternalUserSourceController extends Controller
{
    public function __construct(
        private readonly ExternalUserSourceService $externalUserSourceService,
        private readonly LdapService $ldapService,
        private readonly OidcService $oidcService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $sources = $this->externalUserSourceService->getAllWithRelations();

        return response()->json($sources);
    }

    public function store(StoreExternalUserSourceRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $source = $this->externalUserSourceService->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($source, 201);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_source.success.create'));
    }

    public function update(
        UpdateExternalUserSourceRequest $request,
        ExternalUserSource $externalUserSource
    ): RedirectResponse|JsonResponse {
        $this->authorize('view', GeneralSettings::class);

        $source = $this->externalUserSourceService->update($externalUserSource, $request->validated());

        if ($request->wantsJson()) {
            return response()->json($source);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_source.success.update'));
    }

    public function destroy(ExternalUserSource $externalUserSource): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $this->externalUserSourceService->delete($externalUserSource);

        if (request()->wantsJson()) {
            return response()->json(['message' => __('flash-messages.external_user_source.success.delete')]);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_source.success.delete'));
    }

    public function testConnection(ExternalUserSource $externalUserSource): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        return $this->runConnectionTest($externalUserSource);
    }

    public function testConnectionConfig(Request $request): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $request->validate([
            'type' => ['sometimes', 'string', 'in:ldap,identity_provider'],
            'config' => ['required', 'array'],
        ]);

        // Create a temporary ExternalUserSource instance for testing
        $tempSource = new ExternalUserSource();
        $tempSource->type = $request->input('type', 'ldap');
        $tempSource->config = $request->input('config');

        return $this->runConnectionTest($tempSource);
    }

    private function runConnectionTest(ExternalUserSource $source): JsonResponse
    {
        if ($source->type === 'identity_provider') {
            try {
                $success = $this->oidcService->testConnection($source);

                return response()->json([
                    'success' => $success,
                    'message' => $success
                        ? __('OIDC discovery successful')
                        : __('OIDC discovery failed. Please check your configuration.')
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('OIDC discovery failed. Please check your configuration.')
                ], 500);
            }
        }

        try {
            $success = $this->ldapService->testConnection($source);

            return response()->json([
                'success' => $success,
                'message' => $success
                    ? __('LDAP connection successful')
                    : __('LDAP connection failed. Please check your configuration.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('LDAP connection failed. Please check your configuration.')
            ], 500);
        }
    }
}

