<?php

namespace App\Http\Controllers;

use App\Exports\SageBookingLogExport;
use App\Http\Requests\DeleteSageBookingDaysRequest;
use App\Http\Requests\InitializeSageSpecificDayRequest;
use Artwork\Core\Api\Models\ApiLog;
use Artwork\Core\Console\Commands\ImportSage100ApiDataCommand;
use Artwork\Modules\Budget\Models\SageBookingLog;
use Artwork\Modules\Budget\Services\SageBookingDataDeleteService;
use Artwork\Modules\Budget\Services\TableColumnOrderService;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;
use Laravel\Passport\Token;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ToolSettingsInterfacesController extends Controller
{
    public function __construct(
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageBookingDataDeleteService $sageBookingDataDeleteService,
        private readonly TableColumnOrderService $tableColumnOrderService
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        // Die Seite bündelt zwei getrennt berechtigte Bereiche: API-Tokens/Sage ("change tool
        // settings" via TokenPolicy) und Webhooks (eigenständige Permission "manage webhooks").
        // Wer nur eine der beiden hält, darf die Seite öffnen und sieht nur seinen Bereich.
        $canManageTokens = $request->user()?->can('view', Token::class) ?? false;
        $canManageWebhooks = $request->user()?->can('viewAny', WebhookEndpoint::class) ?? false;

        if (!$canManageTokens && !$canManageWebhooks) {
            $this->authorize('view', Token::class);
        }

        $tokens = $canManageTokens
            ? Token::query()
                ->orderBy('name')
                ->get()
                ->map(function (Token $token): array {
                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'revoked' => $token->revoked,
                        'created_at' => $token->created_at,
                        'expires_at' => $token->expires_at,
                        'scopes' => $token->scopes,
                    ];
                })
            : collect();

        return Inertia::render(
            'Interfaces/Index',
            [
                'sageSettings' => $canManageTokens ? $this->sageApiSettingsService->getFirst() : null,
                'tableColumnOrder' => $this->tableColumnOrderService->getAllOrderedByPosition(),
                'canManageTokens' => $canManageTokens,
                'tokens' => $tokens,
                'availableScopes' => $canManageTokens
                    ? Passport::scopes()
                        ->map(fn (Scope $scope): array => [
                            'id' => $scope->id,
                            'description' => $scope->description,
                        ])
                        ->values()
                    : collect(),
                'canManageWebhooks' => $canManageWebhooks,
                'webhookEndpoints' => $canManageWebhooks
                    ? WebhookEndpoint::query()->orderBy('name')->get()
                    : [],
                'webhookEvents' => $canManageWebhooks
                    ? collect(config('webhooks.events', []))
                        ->map(fn (string $description, string $name): array => [
                            'name' => $name,
                            'description' => $description,
                        ])
                        ->values()
                    : [],
            ]
        );
    }

    public function tokenLogs(Token $token): JsonResponse
    {
        $this->authorize('view', Token::class);

        $logs = ApiLog::query()
            ->where('passport_token_id', $token->getKey())
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'logs' => $logs,
        ]);
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
        $this->authorize('updateInterfaceSettings', SageApiSettings::class);

        if (Artisan::call(ImportSage100ApiDataCommand::class) === 0) {
            return Redirect::back()->with('success', __('flash-messages.interfaces.import_executed_successfully'));
        }

        return Redirect::back()->with('error', __('flash-messages.interfaces.import_executed_unsuccessfully'));
    }

    public function initializeSageSpecificDay(InitializeSageSpecificDayRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $specificDayFrom = $validated['specificDayFrom'] ?? null;
        $specificDayTo = $validated['specificDayTo'] ?? $specificDayFrom;
        $ktr = $validated['ktr'] ?? null;

        $parameters = [
            'specificDayFrom' => $specificDayFrom ?: null,
            'specificDayTo' => $specificDayTo ?: null,
        ];

        if ($ktr) {
            $parameters['--ktr'] = $ktr;
        }

        if (Artisan::call(ImportSage100ApiDataCommand::class, $parameters) === 0) {
            return Redirect::back()->with('success', __('flash-messages.interfaces.import_executed_successfully'));
        }

        return Redirect::back()->with('error', __('flash-messages.interfaces.import_executed_unsuccessfully'));
    }

    public function deleteSageData(): RedirectResponse
    {
        $this->authorize('updateInterfaceSettings', SageApiSettings::class);

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

    public function deleteSageBookingDays(DeleteSageBookingDaysRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ktr = $validated['ktr'] ?? null;
        $dateFrom = $validated['dateFrom'] ?? null;
        $dateTo = $validated['dateTo'] ?? $dateFrom;
        $deleteAssignedData = $validated['deleteAssignedData'] ?? false;

        try {
            $this->sageBookingDataDeleteService->deleteByBookingCriteria(
                $ktr,
                $dateFrom ?: null,
                $dateTo ?: null,
                $deleteAssignedData
            );

            return Redirect::back()->with('success', __('flash-messages.interfaces.booking_days_deleted_successfully'));
        } catch (Throwable $t) {
            return Redirect::back()->with('error', $t->getMessage());
        }
    }

    /**
     * Paginated list of Sage import/delete runs (JSON, consumed by the log modal).
     *
     * @throws AuthorizationException
     */
    public function sageLogs(Request $request): JsonResponse
    {
        $this->authorize('view', Token::class);

        // clampen: perPage=0 wirft DivisionByZeroError (500), riesige Werte liefern
        // den kompletten Log als eine Antwort
        $logs = SageBookingLog::with('user:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->paginate(min(max((int) $request->integer('perPage', 15), 1), 100));

        return response()->json(['logs' => $logs]);
    }

    /**
     * Paginated entries (affected Sage records) of a single run.
     *
     * @throws AuthorizationException
     */
    public function sageLogEntries(Request $request, SageBookingLog $sageBookingLog): JsonResponse
    {
        $this->authorize('view', Token::class);

        $entries = $sageBookingLog->entries()
            ->orderBy('id')
            ->paginate(min(max((int) $request->integer('perPage', 25), 1), 100));

        return response()->json([
            'log' => $sageBookingLog->load('user:id,first_name,last_name'),
            'entries' => $entries,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function sageLogExport(SageBookingLog $sageBookingLog): BinaryFileResponse
    {
        $this->authorize('view', Token::class);

        return (new SageBookingLogExport($sageBookingLog))
            ->download('sage-log-' . $sageBookingLog->id . '.csv', Excel::CSV);
    }
}
