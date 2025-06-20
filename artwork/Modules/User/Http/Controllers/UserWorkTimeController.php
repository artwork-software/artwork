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
        $user->workTime()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );
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
