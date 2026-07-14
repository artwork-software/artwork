<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalUserManagement\Http\Requests\StoreExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Http\Requests\UpdateExternalUserSourceRequest;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSourceService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSyncService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;

class ExternalUserSourceController extends Controller
{
    public function __construct(
        private readonly ExternalUserSourceService $externalUserSourceService,
        private readonly LdapService $ldapService,
        private readonly OidcService $oidcService,
        private readonly ExternalUserSyncService $externalUserSyncService
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

    public function sync(ExternalUserSource $externalUserSource): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        if ($externalUserSource->type !== 'ldap') {
            return response()->json([
                'success' => false,
                'message' => __('Manual sync is only available for LDAP / Active Directory sources.'),
            ], 422);
        }

        if (!$externalUserSource->active) {
            return response()->json([
                'success' => false,
                'message' => __('This source is inactive. Activate it before syncing.'),
            ], 422);
        }

        // Verhindert parallele Läufe (manueller Klick vs. 30-Minuten-Scheduler):
        // doppelte firstOrCreate-Races und doppelte Willkommens-Mails
        $lock = Cache::lock('external-user-sync-' . $externalUserSource->id, 600);
        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => __('A sync for this source is already running.'),
            ], 409);
        }

        try {
            $result = $this->externalUserSyncService->syncSource($externalUserSource);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => __('Sync failed. Please check the configuration and the logs.'),
            ], 500);
        } finally {
            $lock->release();
        }

        return response()->json([
            'success' => true,
            'message' => __(':synced of :total users synced (:skipped skipped).', [
                'synced' => $result['synced'],
                'total' => $result['total'],
                'skipped' => $result['skipped'],
            ]),
            'result' => $result,
        ]);
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('LDAP connection failed. Please check your configuration.')
            ], 500);
        }

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => __('LDAP connection failed. Please check your configuration.')
            ]);
        }

        // Verbindung steht – zusätzlich die ersten gefundenen User zur Kontrolle laden.
        try {
            $users = $this->ldapService->previewUsers($source, 3);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => __('LDAP connection successful, but the user search failed.'),
                'users' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('LDAP connection successful'),
            'users' => $users,
        ]);
    }
}

