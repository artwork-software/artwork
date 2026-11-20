<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Http\Requests\ContractStoreRequest;
use Artwork\Modules\Contract\Http\Requests\ContractUpdateRequest;
use Artwork\Modules\Contract\Http\Resources\ContractListResource;
use Artwork\Modules\Contract\Http\Resources\ContractResource;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Http\Resources\ContractModuleResource;
use Artwork\Modules\Contract\Models\ContractModule;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Contract\Services\ContractService;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\UpdateProjectContractsDocuments;
use Artwork\Modules\Project\Services\ProjectTabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Artwork\Modules\Shift\Support\SafeBroadcast;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractService $contractService,
        private readonly ProjectTabService $projectTabService
    ) {
    }

    public function index(): Response|ResponseFactory
    {
        $user = Auth::user();

        // Schlanke Zeilen für die Übersicht; den vollständigen Vertrag lädt das Bearbeiten-Fenster
        // über contracts.details nach.
        $contracts = $this->contractService->visibleContractsQuery($user)
            ->with([
                'project.users',
                'contract_type',
                'company_type',
                'currency',
                'accessingUsers',
                'accessingDepartments',
                'tasks',
            ])
            ->get();

        // Load saved contract filter for current user
        $savedFilter = $user->contractFilter;

        return inertia('Contracts/ContractManagement', [
            'contracts' => ContractListResource::collection($contracts)->resolve(),
            'contract_modules' => ContractModuleResource::collection(ContractModule::select(['id', 'name'])->get()),
            'contract_types' => ContractType::select(['id', 'name', 'color'])->get(),
            'company_types' => CompanyType::select(['id', 'name', 'color'])->get(),
            'currencies' => Currency::select(['id', 'name'])->get(),
            'first_project_tab_id' => $this->projectTabService->getFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'saved_filter' => $savedFilter ? [
                'kskLiable' => $savedFilter->ksk_liable,
                'foreignTax' => $savedFilter->foreign_tax,
                'dateFrom' => $savedFilter->date_from,
                'dateTo' => $savedFilter->date_to,
                'legalFormIds' => $savedFilter->legal_form_ids ?? [],
                'contractTypeIds' => $savedFilter->contract_type_ids ?? [],
            ] : null,
        ]);
    }

    public function store(ContractStoreRequest $request, Project $project): RedirectResponse
    {
        $this->authorizeContractStore($request, $project);

        $data = $request->data();
        $contract = $this->contractService->createContract(
            $data,
            $project,
            $request->file('file'),
            $request->input('document_request_id')
        );

        if (isset($request->tasks) || $request->comment) {
            $this->contractService->storeTasksAndComments($contract, $request->tasks, $request->comment);
        }

        return Redirect::back();
    }

    /**
     * Schreibrecht im Projekt, Vertragsrecht mit Projektsicht oder Beteiligung an einer
     * Dokumentenanfrage desselben Projekts.
     */
    private function authorizeContractStore(Request $request, Project $project): void
    {
        $user = $request->user();
        abort_unless((bool) $user, 401);

        // input() statt filled()/integer(): ContractStoreRequest überschreibt data(), was filled()/integer() aushebelt
        $documentRequestId = $request->input('document_request_id');
        $documentRequest = null;
        if ($documentRequestId !== null && $documentRequestId !== '' && !is_array($documentRequestId)) {
            $documentRequest = DocumentRequest::query()->find((int) $documentRequestId);
            abort_unless(
                $documentRequest !== null
                && ($documentRequest->project_id === null || (int) $documentRequest->project_id === (int) $project->id),
                403
            );
        }

        $fulfilsOwnRequest = $documentRequest !== null
            && (int) $documentRequest->project_id === (int) $project->id
            && in_array(
                (int) $user->id,
                [(int) $documentRequest->requested_id, (int) $documentRequest->requester_id],
                true
            );

        abort_unless(
            $fulfilsOwnRequest
            || $user->can('update', $project)
            || ($user->can(PermissionEnum::CONTRACT_EDIT_UPLOAD->value) && $user->can('view', $project)),
            403
        );
    }

    public function download(Contract $contract): StreamedResponse
    {
        $this->authorize('view', $contract);
        return $this->contractService->downloadContract($contract);
    }

    /**
     * Vollständiger Vertrag für das Bearbeiten-Fenster der Übersicht (Aufgaben, Kommentare, Freigaben).
     */
    public function details(Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $contract->load([
            'creator',
            'project',
            'contract_type',
            'company_type',
            'currency',
            'accessingUsers.departments',
            'accessingDepartments',
            'comments',
            'tasks',
        ]);

        return response()->json(['contract' => (new ContractResource($contract))->resolve()]);
    }

    /**
     * Zeigt die Vertragsdatei im Browser an statt sie herunterzuladen. Nur PDF und Bilder: andere
     * hochgeladene Dateitypen (HTML, SVG ...) dürfen nie unter der eigenen Adresse dargestellt werden.
     */
    public function preview(Contract $contract): StreamedResponse
    {
        $this->authorize('view', $contract);
        abort_unless(ContractListResource::isPreviewable($contract), 415);

        $path = 'contracts/' . $contract->basename;
        abort_unless(Storage::exists($path), 404);

        // Typ aus der geprüften Endung, nicht aus dem Dateiinhalt: eine alte Datei mit HTML-Inhalt
        // unter einem PDF-Namen darf nie als HTML ausgeliefert werden
        $extension = strtolower(pathinfo((string) $contract->basename, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/' . $extension,
        };

        return Storage::response($path, $contract->name, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . addcslashes((string) $contract->name, '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Löscht mehrere Verträge auf einmal. Fehlt für einen davon das Recht, wird keiner gelöscht.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $contracts = Contract::query()->whereIn('id', $validated['ids'])->get();
        abort_unless($contracts->count() === count($validated['ids']), 404);
        $contracts->each(fn (Contract $contract) => $this->authorize('delete', $contract));

        $contracts->each(fn (Contract $contract) => $this->contractService->deleteContract($contract));

        return response()->json(['deleted' => $contracts->count()]);
    }

    public function update(Contract $contract, ContractUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', $contract);
        $data = $request->data();
        $data['accessibleUsers'] = $request->accessibleUsers;
        $data['accessibleDepartments'] = $request->accessibleDepartments;

        $this->contractService->updateContract(
            $contract,
            $data,
            $request->file('file')
        );

        if (isset($request->tasks) || $request->comment) {
            $this->contractService->storeTasksAndComments($contract, $request->tasks, $request->comment);
        }

        // Broadcast update for project contracts/documents component
        if ($contract->project_id) {
            SafeBroadcast::send(new UpdateProjectContractsDocuments($contract->project_id));
        }

        return Redirect::back();
    }

    public function storeFile(Request $request): void
    {
        // Fehlende Datei endete in einem TypeError (500), ein unbekannter Vertrag wurde still ignoriert
        $request->validate([
            'contract' => ['required', 'integer', 'exists:contracts,id'],
            'file' => ['required', 'file'],
        ]);

        $contract = Contract::query()->findOrFail($request->integer('contract'));
        $this->authorize('update', $contract);
        $this->contractService->storeContractFile($contract, $request->file('file'));
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);
        $this->contractService->deleteContract($contract);
        return Redirect::back();
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filters = [
            'kskLiable' => $request->boolean('kskLiable'),
            'foreignTax' => $request->boolean('foreignTax'),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'legalFormIds' => $request->input('legalFormIds'),
            'contractTypeIds' => $request->input('contractTypeIds'),
            // Export der in der Übersicht markierten Verträge
            'ids' => array_map(intval(...), array_filter((array) $request->input('ids', []), is_numeric(...))),
        ];

        return $this->contractService->exportContracts(
            $filters,
            Auth::user(),
            Auth::user()->language ?? 'de'
        );
    }

    public function saveFilter(Request $request): JsonResponse
    {
        $filters = [
            'kskLiable' => $request->boolean('kskLiable'),
            'foreignTax' => $request->boolean('foreignTax'),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'legalFormIds' => $request->input('legalFormIds', []),
            'contractTypeIds' => $request->input('contractTypeIds', []),
        ];

        $this->contractService->saveUserFilter(Auth::id(), $filters);

        return response()->json(['success' => true]);
    }
}
