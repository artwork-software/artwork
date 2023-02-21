<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractUpdateRequest;
use App\Http\Resources\ContractModuleResource;
use App\Http\Resources\ContractResource;
use App\Models\Comment;
use App\Models\Contract;
use App\Models\ContractModule;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function Pest\Laravel\json;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Contract::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function viewIndex()
    {
        $contracts = Contract::all();
        return inertia('Contracts/ContractManagement', [
            'contracts' => ContractResource::collection($contracts),
            'contract_modules' => ContractModuleResource::collection(ContractModule::all())
        ]);

    }

    public function index(Request $request)
    {

        $contracts = Contract::all();
        $costsFilter = json_decode($request->input('costsFilter'));
        $companyTypesFilter = json_decode($request->input('companyTypesFilter'));
        $contractTypesFilter = json_decode($request->input('contractTypesFilter'));

        if (count($costsFilter->array) != 0 || count($companyTypesFilter->array) != 0 || count($contractTypesFilter->array) != 0) {
            $company_type_ids = collect($companyTypesFilter->array);
            $contract_type_ids = collect($contractTypesFilter->array);
            $cost_filters = collect($costsFilter->array);

            Debugbar::info($company_type_ids);
            Debugbar::info($cost_filters);

            if ($cost_filters->contains('KSK-pflichtig')) {
                $contracts = $contracts->where('ksk_liable', true);
            }
            if ($cost_filters->contains('Im Ausland ansässig')) {
                $contracts = $contracts->where('resident_abroad', true);
            }
            if (count($company_type_ids) > 0) {
                $contracts = $contracts->whereIn('company_type_id', $company_type_ids);
            }
            if (count($contract_type_ids) > 0) {
                $contracts = $contracts->whereIn('contract_type_id', $contract_type_ids);
            }
        }
        return [
            'contracts' => ContractResource::collection($contracts),
            'contract_modules' => ContractModuleResource::collection(ContractModule::all())
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param Contract $contract
     * @return Response|ResponseFactory
     */
    public function show(Contract $contract)
    {
        return inertia('Contracts/Contracts', [
            'contract' => new ContractResource($contract),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request, Project $project)
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }

        $file = $request->file;
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('contracts', $file, $basename);

        $contract = $project->contracts()->create([
            'name' => $original_name,
            'basename' => $basename,
            'contract_partner' => $request->contract_partner,
            'amount' => $request->amount,
            'project_id' => $project->id,
            'currency_id' => $request->currency_id,
            'description' => $request->description,
            'ksk_liable' => $request->ksk_liable,
            'resident_abroad' => $request->resident_abroad,
            'is_freed' => @$request->is_freed,
            'has_power_of_attorney' => @$request->has_power_of_attorney,
            'contract_type_id' => $request->contract_type_id,
            'company_type_id' => $request->company_type_id
        ]);

        $this->store_contract_tasks_and_comment($request, $contract);

        $contract->accessing_users()->sync(collect($request->accessibleUsers));

        $contract->accessing_users()->save(Auth::user());

        $contract->save();

        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param Contract $contract
     * @return StreamedResponse
     * @throws AuthorizationException
     */
    public function download(Contract $contract): StreamedResponse
    {
        //$this->authorize('view contracts');

        return Storage::download('contracts/' . $contract->basename, $contract->name);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Contract $contract
     * @return RedirectResponse
     */
    public function update(Contract $contract, ContractUpdateRequest $request)
    {

        if ($request->get('accessibleUsers')) {
            $contract->accessing_users()->sync(collect($request->accessibleUsers));
        }

        if($request->file('file')) {
            Storage::delete('contracts/'. $contract->basename);
            $file = $request->file('file');
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20).$original_name;

            $contract->basename = $basename;
            $contract->name = $original_name;

            Storage::putFileAs('contracts', $file, $basename);
        }

        $contract->fill($request->data());

        $this->store_contract_tasks_and_comment($request, $contract);

        $contract->save();

        return Redirect::back();


    }

    public function storeFile(Request $request)
    {

        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }

        $file = $request->file;
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('contracts', $file, $basename);

        $contract = Contract::find($request->contract);
        $contract->basename = $basename;
        $contract->name = $original_name;
        $contract->save();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contract $contract
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contract $contract)
    {
        $contract->delete();
        Redirect::back();
    }

    /**
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Model $contract
     * @return void
     */
    public function store_contract_tasks_and_comment(Request $request, \Illuminate\Database\Eloquent\Model $contract): void
    {
        if (isset($request->tasks)) {
            foreach ($request->tasks as $task_from_req) {
                $task_obj = (object)$task_from_req;
                if (isset($task_obj->new)) {
                    $task = Task::create([
                        'name' => $task_obj->name,
                        'description' => $task_obj->description,
                        'deadline' => $task_obj->deadline,
                        'done' => false,
                        'contract_id' => $contract->id,
                        'order' => 1
                    ]);
                } else {
                    //dd($task_obj);
                    $task = Task::where('id', $task_obj->id)->update(['done' => $task_obj->done]);
                }
            }
        }

        if (isset($task_obj->assigned_users)) {
            foreach ($task_obj->assigned_users as $assigned_user) {
                $user_obj = (object)$assigned_user;
                $user = User::where('id', $user_obj->id)->first();
                $task->task_users()->save($user);
                $user->tasks()->save($task);
            }
        }

        if ($request->comment) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $contract->id
            ]);
            $contract->comments()->save($comment);
        }
    }
}
