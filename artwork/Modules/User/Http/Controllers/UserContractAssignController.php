<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserContractAssignRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserContractAssignRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Nur freigegebene Felder übernehmen – niemals user_id vom Request
        $data = $request->safe()->except(['user_id']);

        try {
            DB::transaction(function () use ($user, $data): void {
                $user->contract()->updateOrCreate([], $data);
            });

            return back()->with('success', __('User contract assigned successfully.'));
        } catch (\Throwable $e) {
            Log::error('Failed to assign user contract', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('Could not assign user contract. Please try again.'));
        }
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
