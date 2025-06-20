<?php

namespace App\Http\Controllers;

use Artwork\Core\Console\Commands\ImportSage100ApiDataCommand;
use Artwork\Modules\Budget\Services\TableColumnOrderService;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Token;
use Throwable;

class ToolSettingsInterfacesController extends Controller
{
    public function __construct(
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly TableColumnOrderService $tableColumnOrderService
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('view', Token::class);

        $tokens = Token::with(['apiAccessToken'])
            ->orderBy('name')
            ->get()
            ->map(function (Token $token): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    "revoked" => $token->revoked,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'last_used_at' => $token->last_used_at,
                    'access_token' => $token?->apiAccessToken?->access_token ?? null,
                ];
            });


        return Inertia::render(
            'Interfaces/Index',
            [
                'sageSettings' => $this->sageApiSettingsService->getFirst(),
                'tableColumnOrder' => $this->tableColumnOrderService->getAllOrderedByPosition(),
                'tokens' => $tokens,
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function createOrUpdate(
        CreateOrUpdateSageApiSettingsRequest $createOrUpdateSageApiSettingsRequest,
    ): RedirectResponse {
        $this->authorize('updateInterfaceSettings', SageApiSettings::class);

        try {
            $this->sageApiSettingsService->createOrUpdateFromRequest($createOrUpdateSageApiSettingsRequest);
        } catch (Throwable $t) {
            return Redirect::back()->with(
                'error',
                __('flash-messages.interfaces.failed_to_save')
            );
        }

        if (!$this->sageApiSettingsService->testConnection()) {
            return Redirect::back()->with(
                'error',
                __('flash-messages.interfaces.connection_test_failed')
            );
        }

        return Redirect::back()->with('success', __('flash-messages.interfaces.saved_successfully'));
    }

    public function initializeSage(): RedirectResponse
    {
        if (Artisan::call(ImportSage100ApiDataCommand::class) === 0) {
            return Redirect::back()->with('success', __('flash-messages.interfaces.import_executed_successfully'));
        }

        return Redirect::back()->with('error', __('flash-messages.interfaces.import_executed_unsuccessfully'));
    }

    public function initializeSageSpecificDay(Request $request): RedirectResponse
    {
        if (
            Artisan::call(
                ImportSage100ApiDataCommand::class,
                [
                    'specificDay' => $request->get('specificDay')
                ]
            ) === 0
        ) {
            return Redirect::back()->with('success', __('flash-messages.interfaces.import_executed_successfully'));
        }

        return Redirect::back()->with('error', __('flash-messages.interfaces.import_executed_unsuccessfully'));
    }

    public function deleteSageData(): RedirectResponse
    {
        // @todo: Controller method is removed in future, logic is preserved as command (can be used for dev purposes)
        try {
            if (Artisan::call(ImportSage100ApiDataCommand::class, ['--delete-sage-data' => true]) === 0) {
                return Redirect::back()->with('success', __('Daten erfolgreich gelöscht.'));
            }
        } catch (Throwable $t) {
            return Redirect::back()->with('error', $t->getMessage());
        }

        return Redirect::back()->with('error', 'Es ist ein unerwarteter Fehler aufgetreten.');
    }
}
