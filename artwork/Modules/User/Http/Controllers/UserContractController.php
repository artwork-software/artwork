<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserContractRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserContractRequest;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Services\UserContractService;
use Inertia\Inertia;

class UserContractController extends Controller
{

    public function __construct(
        protected readonly UserContractService $userContractService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/UserContractSettings/Index', [
            'contracts' => $this->userContractService->getAll()
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserContractRequest $request)
    {
        $data = $request->validated();

        $userContract = $this->userContractService->create($data);

        return redirect()->route('user-contract-settings.index')
            ->with('success', __('User contract created successfully.'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserContractRequest $request, UserContract $userContract)
    {
        $data = $request->validated();

        $this->userContractService->update($userContract, $data);

        return redirect()->route('user-contract-settings.index')
            ->with('success', __('User contract updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserContract $userContract)
    {
        $this->userContractService->delete($userContract);

        return redirect()->route('user-contract-settings.index')
            ->with('success', __('User contract deleted successfully.'));
    }
}
