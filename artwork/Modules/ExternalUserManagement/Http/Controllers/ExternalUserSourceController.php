<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalUserManagement\Http\Requests\StoreExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Http\Requests\UpdateExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
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
        private readonly LdapService $ldapService
    ) {
    }
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $sources = $this->externalUserSourceService->getAllWithRelations();

        return response()->json($sources);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(StoreExternalUserSourceRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $source = $this->externalUserSourceService->create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($source, 201);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_source.success.create'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
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

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(ExternalUserSource $externalUserSource): RedirectResponse|JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $this->externalUserSourceService->delete($externalUserSource);

        if (request()->wantsJson()) {
            return response()->json(['message' => __('flash-messages.external_user_source.success.delete')]);
        }

        return Redirect::back()->with('success', __('flash-messages.external_user_source.success.delete'));
    }

    /**
     * Test the LDAP connection for a source
     *
     * @throws AuthorizationException
     */
    public function testConnection(ExternalUserSource $externalUserSource): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        if ($externalUserSource->type !== 'ldap') {
            return response()->json([
                'success' => false,
                'message' => __('Only LDAP sources can be tested')
            ], 400);
        }

        try {
            $success = $this->ldapService->testConnection($externalUserSource);

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

    /**
     * Test the LDAP connection with provided config data (without saving)
     *
     * @throws AuthorizationException
     */
    public function testConnectionConfig(Request $request): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        $request->validate([
            'config' => ['required', 'array'],
            'config.host' => ['required', 'string'],
            'config.port' => ['required', 'integer'],
            'config.base_dn' => ['required', 'string'],
            'config.bind_dn' => ['required', 'string'],
            'config.bind_password' => ['required', 'string'],
            'config.use_ssl' => ['sometimes', 'boolean'],
            'config.use_tls' => ['sometimes', 'boolean'],
        ]);

        // Create a temporary ExternalUserSource instance for testing
        $tempSource = new ExternalUserSource();
        $tempSource->type = 'ldap';
        $tempSource->config = $request->input('config');

        try {
            $success = $this->ldapService->testConnection($tempSource);

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

