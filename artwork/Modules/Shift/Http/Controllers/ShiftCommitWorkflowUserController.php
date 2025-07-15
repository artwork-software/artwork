<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Http\Requests\StoreShiftCommitWorkflowUserRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftCommitWorkflowUserRequest;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;

class ShiftCommitWorkflowUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftCommitWorkflowUserRequest $request)
    {
        $userIds = $request->input('users', []);

        if (empty($userIds)) {
            return redirect()->back()->withErrors(['users' => 'Mindestens ein Benutzer muss ausgewählt werden.']);
        }

        // Nur gültige Benutzer-IDs verwenden
        $validUserIds = User::whereIn('id', $userIds)->pluck('id')->toArray();

        $existingUserIds = ShiftCommitWorkflowUser::whereIn('user_id', $validUserIds)->pluck('user_id')->toArray();
        $newUserIds = array_diff($validUserIds, $existingUserIds);

        $newEntries = array_map(fn($id) => ['user_id' => $id], $newUserIds);
        ShiftCommitWorkflowUser::insert($newEntries);

        return redirect()->back()->with('success', 'Benutzer wurden erfolgreich zum Shift-Commit-Workflow hinzugefügt.');
    }



    /**
     * Display the specified resource.
     */
    public function show(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftCommitWorkflowUserRequest $request, ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        $shiftCommitWorkflowUser->delete();

        return redirect()->back()->with('success', 'User removed from shift commit workflow successfully.');
    }
}
