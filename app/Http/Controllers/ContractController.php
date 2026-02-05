<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Http\Requests\ContractStoreRequest;
use Artwork\Modules\Contract\Http\Requests\ContractUpdateRequest;
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
use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Admins can see all contracts
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            $contracts = Contract::all();
        } else {
            // get all contracts where i am creator or i am accessing user or in accessing department
            $userDepartmentIds = $user->departments->pluck('id')->toArray();

            $contracts = Contract::where('creator_id', $user->id)
                ->orWhereHas('accessingUsers', function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                })
                ->orWhereHas('accessingDepartments', function ($query) use ($userDepartmentIds): void {
                    $query->whereIn('department_id', $userDepartmentIds);
                })
                ->get();
        }

        // Load saved contract filter for current user
        $savedFilter = $user->contractFilter;

        return inertia('Contracts/ContractManagement', [
            'contracts' => ContractResource::collection($contracts)->resolve(),
            'contract_modules' => ContractModuleResource::collection(ContractModule::all()),
            'contract_types' => ContractType::all(),
            'company_types' => CompanyType::all(),
            'currencies' => Currency::all(),
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

    public function show(Contract $contract): Response|ResponseFactory
    {
        return inertia('Contracts/Contracts', [
            'contract' => new ContractResource($contract),
        ]);
    }

    public function store(ContractStoreRequest $request, Project $project): RedirectResponse
    {
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

    public function download(Contract $contract): StreamedResponse
    {
        return $this->contractService->downloadContract($contract);
    }

    public function update(Contract $contract, ContractUpdateRequest $request): RedirectResponse
    {
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
            broadcast(new UpdateProjectContractsDocuments($contract->project_id));
        }

        return Redirect::back();
    }

    public function storeFile(Request $request): void
    {
        $contract = Contract::find($request->contract);
        $this->contractService->storeContractFile($contract, $request->file);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
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
        ];

        return $this->contractService->exportContracts(
            $filters,
            Auth::id(),
            Auth::user()->language ?? 'de'
        );
    }

    public function saveFilter(Request $request): \Illuminate\Http\JsonResponse
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
