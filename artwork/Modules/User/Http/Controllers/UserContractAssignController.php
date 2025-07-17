<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserContractAssignRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserContractAssignRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;

class UserContractAssignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserContractAssignRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $user->contract()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return back()->with('success', __('User contract assigned successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserContractAssignRequest $request, UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserContractAssign $userContractAssign): void
    {
        //
    }
}
