<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserWorkTimeRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserWorkTimeRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;

class UserWorkTimeController extends Controller
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
    public function store(StoreUserWorkTimeRequest $request, User $user): void
    {
        $validated = $request->validated();

        // if in the request there is an id, we update the existing work time
        if (isset($validated['id'])) {
            $userWorkTime = UserWorkTime::findOrFail($validated['id']);
            $userWorkTime->update($validated);
            return;
        }


        $validated['user_id'] = $user->id;

        // Prüfen, ob gültig für heute
        $today = now()->toDateString();

        $validFrom = $validated['valid_from'] ?? null;
        $validUntil = $validated['valid_until'] ?? null;

        $isActive =
            (!$validFrom || $validFrom <= $today) &&
            (!$validUntil || $validUntil >= $today);

        $validated['is_active'] = $isActive;

        UserWorkTime::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserWorkTime $userWorkTime): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserWorkTime $userWorkTime): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserWorkTimeRequest $request, UserWorkTime $userWorkTime): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserWorkTime $userWorkTime): void
    {
        //
    }
}
